<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stock of a product on past days, worked back from today's stock:
 *   closing of a day = today's stock − every change made after that day
 *   opening of a day = closing − the changes made during that day
 * Changes are the stock history (sales, purchases, returns) and stock adjustments.
 *
 * Stock that came in without a purchase counts as received, like a purchase:
 *   - stock-in adjustments ("add" with a quantity),
 *   - opening stock of imported products,
 *   - the stock a product had when it was first added without any record of it
 *     (today's stock − every recorded change), dated when the product was created.
 */
class StockLedger
{
    /**
     * Day by day stock for the given products, from $from to $to (inclusive).
     *
     * @return array [product_id => [Y-m-d => ['opening', 'received', 'sold', 'adjusted', 'closing']]], days newest first
     */
    public function daily($productIds, Carbon $from, Carbon $to): array
    {
        $ids = $this->ids($productIds);
        if ($ids->isEmpty()) {
            return [];
        }
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $products = Product::whereIn('id', $ids)->get(['id', 'current_stock', 'created_at'])->keyBy('id');
        $initial = $this->unrecordedInitialStock($ids);

        // Everything changed after the last day, per product
        $after = $this->sumByProduct(
            StockHistory::whereIn('product_id', $ids)->where('created_at', '>', $to),
            'quantity_change'
        );
        $adjustedAfter = $this->sumByProduct(
            StockAdjustment::whereIn('product_id', $ids)->where('created_at', '>', $to),
            $this->signedAdjustment()
        );

        // Changes during the period, per product and day
        $history = StockHistory::whereIn('product_id', $ids)->whereBetween('created_at', [$from, $to])
            ->selectRaw("product_id, DATE(created_at) as day,
                SUM(CASE WHEN type IN ('Purchase', 'Opening Stock') THEN quantity_change ELSE 0 END) as received,
                SUM(CASE WHEN type = 'Sale' THEN -quantity_change ELSE 0 END) as sold,
                SUM(CASE WHEN type NOT IN ('Purchase', 'Opening Stock', 'Sale') THEN quantity_change ELSE 0 END) as adjusted")
            ->groupBy('product_id', DB::raw('DATE(created_at)'))
            ->get();
        $adjustments = StockAdjustment::whereIn('product_id', $ids)->whereBetween('created_at', [$from, $to])
            ->selectRaw("product_id, DATE(created_at) as day,
                SUM(CASE WHEN adjustment_type = 'add' THEN quantity ELSE 0 END) as received,
                SUM(CASE WHEN adjustment_type = 'add' THEN 0 ELSE -quantity END) as adjusted")
            ->groupBy('product_id', DB::raw('DATE(created_at)'))
            ->get();

        $empty = ['received' => 0.0, 'sold' => 0.0, 'adjusted' => 0.0];
        $changes = [];
        foreach ($history as $row) {
            $day = Carbon::parse($row->day)->toDateString();
            $changes[$row->product_id][$day] = [
                'received' => (float) $row->received,
                'sold' => (float) $row->sold,
                'adjusted' => (float) $row->adjusted,
            ];
        }
        foreach ($adjustments as $row) {
            $day = Carbon::parse($row->day)->toDateString();
            $changes[$row->product_id][$day] ??= $empty;
            $changes[$row->product_id][$day]['received'] += (float) $row->received;
            $changes[$row->product_id][$day]['adjusted'] += (float) $row->adjusted;
        }

        $result = [];
        foreach ($ids as $id) {
            $product = $products->get($id);
            $closing = (float) optional($product)->current_stock - (float) ($after[$id] ?? 0) - (float) ($adjustedAfter[$id] ?? 0);

            // Unrecorded first stock arrives on the day the product was created
            if (($initial[$id] ?? 0) > 0 && $product) {
                $created = $this->createdAt($product);
                if ($created->gt($to)) {
                    $closing -= $initial[$id];
                } elseif ($created->gte($from)) {
                    $day = $created->toDateString();
                    $changes[$id][$day] ??= $empty;
                    $changes[$id][$day]['received'] += $initial[$id];
                }
            }

            for ($day = $to->copy()->startOfDay(); $day->gte($from); $day->subDay()) {
                $key = $day->toDateString();
                $c = $changes[$id][$key] ?? $empty;
                $opening = $closing - $c['received'] + $c['sold'] - $c['adjusted'];
                $result[$id][$key] = [
                    'opening' => round($opening, 2),
                    'received' => round($c['received'], 2),
                    'sold' => round($c['sold'], 2),
                    'adjusted' => round($c['adjusted'], 2),
                    'closing' => round($closing, 2),
                ];
                $closing = $opening;
            }
        }

        return $result;
    }

    /** Stock for one day: [product_id => ['opening', 'received', 'sold', 'adjusted', 'closing']] */
    public function forDay($productIds, Carbon $day): array
    {
        return collect($this->daily($productIds, $day, $day))
            ->map(fn ($days) => reset($days))
            ->all();
    }

    /**
     * Stock that came in without a purchase, oldest first, optionally within a period.
     * Each entry: ['product_id', 'at' (Carbon), 'kind' (initial|opening|adjustment), 'quantity', 'unit_cost', 'user', 'note']
     */
    public function stockIn($productIds, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $ids = $this->ids($productIds);
        if ($ids->isEmpty()) {
            return [];
        }
        $within = function ($query) use ($from, $to) {
            if ($from) {
                $query->where('created_at', '>=', $from);
            }
            if ($to) {
                $query->where('created_at', '<=', $to);
            }
            return $query;
        };
        $products = Product::whereIn('id', $ids)->get(['id', 'purchase_price', 'created_at'])->keyBy('id');
        $entries = [];

        foreach ($this->unrecordedInitialStock($ids) as $id => $qty) {
            $product = $products->get($id);
            $at = $product ? $this->createdAt($product) : null;
            if ($qty > 0 && $at && (!$from || $at->gte($from)) && (!$to || $at->lte($to))) {
                $entries[] = ['product_id' => $id, 'at' => $at, 'kind' => 'initial', 'quantity' => $qty,
                    'unit_cost' => (float) $product->purchase_price, 'user' => null,
                    'note' => 'Stock when the product was added'];
            }
        }

        $within(StockHistory::with('user')->whereIn('product_id', $ids)->where('type', 'Opening Stock')->where('quantity_change', '>', 0))
            ->get()
            ->each(function ($row) use (&$entries) {
                $entries[] = ['product_id' => $row->product_id, 'at' => $row->created_at, 'kind' => 'opening',
                    'quantity' => (float) $row->quantity_change, 'unit_cost' => (float) $row->unit_cost,
                    'user' => $row->user, 'note' => trim('Opening stock ' . ($row->reference_no ?? ''))];
            });

        $within(StockAdjustment::with('user')->whereIn('product_id', $ids)->where('adjustment_type', 'add')->where('quantity', '>', 0))
            ->get()
            ->each(function ($row) use (&$entries, $products) {
                $entries[] = ['product_id' => $row->product_id, 'at' => $row->created_at, 'kind' => 'adjustment',
                    'quantity' => (float) $row->quantity, 'unit_cost' => (float) optional($products->get($row->product_id))->purchase_price,
                    'user' => $row->user, 'note' => $row->reason];
            });

        usort($entries, fn ($a, $b) => $a['at'] <=> $b['at']);

        return $entries;
    }

    /** Total stock that came in without a purchase, per product: [product_id => quantity] */
    public function stockInTotals($productIds): array
    {
        $totals = [];
        foreach ($this->stockIn($productIds) as $entry) {
            $totals[$entry['product_id']] = ($totals[$entry['product_id']] ?? 0) + $entry['quantity'];
        }

        return $totals;
    }

    /**
     * Stock each product had before any recorded change: today's stock minus all
     * recorded changes. Above zero means stock was entered with no record of it.
     */
    private function unrecordedInitialStock($ids): array
    {
        $current = Product::whereIn('id', $ids)->pluck('current_stock', 'id');
        $history = $this->sumByProduct(StockHistory::whereIn('product_id', $ids), 'quantity_change');
        $adjusted = $this->sumByProduct(StockAdjustment::whereIn('product_id', $ids), $this->signedAdjustment());

        $initial = [];
        foreach ($current as $id => $stock) {
            $qty = round((float) $stock - (float) ($history[$id] ?? 0) - (float) ($adjusted[$id] ?? 0), 2);
            if ($qty > 0) {
                $initial[$id] = $qty;
            }
        }

        return $initial;
    }

    private function createdAt(Product $product): Carbon
    {
        return $product->created_at ? Carbon::parse($product->created_at) : Carbon::create(2000);
    }

    private function ids($productIds)
    {
        return collect($productIds)->map(fn ($id) => (int) $id)->unique()->values();
    }

    private function sumByProduct($query, string $expression)
    {
        return $query->groupBy('product_id')
            ->selectRaw("product_id, SUM({$expression}) as total")
            ->pluck('total', 'product_id');
    }

    private function signedAdjustment(): string
    {
        return "CASE WHEN adjustment_type = 'add' THEN quantity ELSE -quantity END";
    }
}
