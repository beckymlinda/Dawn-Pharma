<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use App\Services\StockLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    /**
     * List products with search and pagination.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'unit'])
            ->withSum('saleItems as total_sold', 'quantity')
            ->withSum('purchaseItems as total_purchased', 'quantity')
            ->withMax('saleItems as last_sold_at', 'created_at')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $products = $query->paginate(25)->withQueryString();

        // Opening and closing stock of each listed product on the chosen day (default today)
        $stockDay = $this->day($request->input('date')) ?? today();
        $dailyStock = app(StockLedger::class)->forDay($products->pluck('id'), $stockDay);

        // Stock that came in without a purchase (stock-in adjustments, first stock) counts as purchased
        $stockIn = app(StockLedger::class)->stockInTotals($products->pluck('id'));
        $products->getCollection()->each(function ($product) use ($stockIn) {
            $product->stock_in = (float) ($stockIn[$product->id] ?? 0);
            $product->total_purchased = (float) $product->total_purchased + $product->stock_in;
        });

        return view('reports.products.index', compact('products', 'stockDay', 'dailyStock'));
    }

    /**
     * Full history of a single product: sales, purchases, adjustments,
     * stock movements and activity, each with the user who did it.
     */
    public function show(Request $request, Product $product)
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;
        $activeTab = in_array($request->input('tab'), ['sales', 'purchases', 'adjustments', 'movements', 'daily', 'activity'])
            ? $request->input('tab')
            : 'sales';

        $inRange = function ($query, string $column) use ($from, $to) {
            if ($from) {
                $query->where($column, '>=', $from);
            }
            if ($to) {
                $query->where($column, '<=', $to);
            }
            return $query;
        };

        // Sales
        $salesQuery = SaleItem::where('sale_items.product_id', $product->id)
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id');
        $inRange($salesQuery, 'sales.created_at');

        $salesTotals = (clone $salesQuery)
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as qty, COALESCE(SUM(sale_items.subtotal), 0) as amount, COUNT(DISTINCT sales.id) as count')
            ->first();
        $salesPerDay = (clone $salesQuery)
            ->selectRaw('DATE(sales.created_at) as day, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as amount')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $sales = $salesQuery
            ->select('sale_items.*', 'sales.created_at as sold_at')
            ->with(['sale.user', 'sale.register.user', 'sale.editor'])
            ->orderByDesc('sales.created_at')
            ->paginate(25, ['*'], 'sales_page')
            ->appends($request->except('sales_page') + ['tab' => 'sales']);

        // Purchases
        $purchasesQuery = PurchaseItem::where('purchase_items.product_id', $product->id)
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id');
        $inRange($purchasesQuery, 'purchases.purchase_date');

        $purchaseTotals = (clone $purchasesQuery)
            ->selectRaw('COALESCE(SUM(purchase_items.quantity), 0) as qty, COALESCE(SUM(purchase_items.line_total), 0) as amount, COUNT(DISTINCT purchases.id) as count')
            ->first();
        $purchasesPerDay = (clone $purchasesQuery)
            ->selectRaw('DATE(purchases.purchase_date) as day, SUM(purchase_items.quantity) as qty, SUM(purchase_items.line_total) as amount')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $purchaseRows = $purchasesQuery
            ->select('purchase_items.*', 'purchases.purchase_date as purchased_at')
            ->with(['purchase.user', 'purchase.supplier'])
            ->get()
            ->map(fn ($item) => (object) [
                'at' => Carbon::parse($item->purchased_at),
                'sort_id' => $item->id,
                'kind' => 'purchase',
                'reference' => $item->purchase->reference_no ?? null,
                'purchase' => $item->purchase,
                'supplier' => $item->purchase->supplier->name ?? null,
                'status' => $item->purchase->status ?? null,
                'quantity' => (float) $item->quantity,
                'unit_cost' => (float) $item->unit_cost,
                'line_total' => (float) $item->line_total,
                'expiry' => $item->expiry_date ?? $item->exp_date,
                'by' => $item->purchase->user->name ?? null,
            ]);

        // Stock in without a purchase record (stock-in adjustments, first stock) is a purchase too
        $stockIn = collect(app(StockLedger::class)->stockIn([$product->id], $from, $to))
            ->map(fn ($entry) => (object) [
                'at' => $entry['at'],
                'sort_id' => 0,
                'kind' => $entry['kind'],
                'reference' => $entry['note'],
                'purchase' => null,
                'supplier' => null,
                'status' => 'received',
                'quantity' => $entry['quantity'],
                'unit_cost' => $entry['unit_cost'],
                'line_total' => round($entry['quantity'] * $entry['unit_cost'], 2),
                'expiry' => null,
                'by' => $entry['user']->name ?? null,
            ]);
        foreach ($stockIn as $entry) {
            $day = $entry->at->toDateString();
            $row = $purchasesPerDay[$day] ?? (object) ['day' => $day, 'qty' => 0, 'amount' => 0];
            $row->qty += $entry->quantity;
            $row->amount += $entry->line_total;
            $purchasesPerDay[$day] = $row;
        }
        $purchaseTotals->qty += $stockIn->sum('quantity');
        $purchaseTotals->amount += $stockIn->sum('line_total');
        $purchaseTotals->count += $stockIn->count();

        // Newest first, 25 per page
        $allPurchases = $purchaseRows->concat($stockIn)
            ->sortByDesc(fn ($r) => sprintf('%s-%010d', $r->at->format('Y-m-d H:i:s'), $r->sort_id))
            ->values();
        $page = max(1, (int) $request->input('purchases_page', 1));
        $purchases = (new \Illuminate\Pagination\LengthAwarePaginator(
            $allPurchases->forPage($page, 25)->values(), $allPurchases->count(), 25, $page,
            ['path' => $request->url(), 'pageName' => 'purchases_page']
        ))->appends($request->except('purchases_page') + ['tab' => 'purchases']);

        // Stock adjustments
        $adjustments = $inRange(StockAdjustment::with('user')->where('product_id', $product->id), 'created_at')
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'adjustments_page')
            ->appends($request->except('adjustments_page') + ['tab' => 'adjustments']);

        // Stock movements (opening stock, purchases, sales, ...)
        $movements = $inRange(StockHistory::with('user')->where('product_id', $product->id), 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'movements_page')
            ->appends($request->except('movements_page') + ['tab' => 'movements']);

        // Product activity log (created / updated / deleted)
        $activities = $inRange(
            ActivityLog::with('user')->where('subject_type', Product::class)->where('subject_id', $product->id),
            'created_at'
        )
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'activity_page')
            ->appends($request->except('activity_page') + ['tab' => 'activity']);

        // Daily stock: opening and closing for each day of the period (default the last 30 days)
        // (never later than today, and at most a year at a time)
        $dailyTo = $to && $to->lt(today()) ? $to->copy()->startOfDay() : today();
        $dailyFrom = $from ? $from->copy()->startOfDay() : $dailyTo->copy()->subDays(29);
        if ($dailyFrom->lt($dailyTo->copy()->subDays(365))) {
            $dailyFrom = $dailyTo->copy()->subDays(365);
        }
        $dailyStock = $dailyFrom->lte($dailyTo)
            ? (app(StockLedger::class)->daily([$product->id], $dailyFrom, $dailyTo)[$product->id] ?? [])
            : [];

        $product->load(['category', 'brand', 'unit', 'supplier']);

        return view('reports.products.show', compact(
            'product', 'activeTab', 'sales', 'salesTotals', 'salesPerDay',
            'purchases', 'purchaseTotals', 'purchasesPerDay',
            'adjustments', 'movements', 'activities', 'dailyStock'
        ));
    }

    /** A Y-m-d date from the request, not later than today; null if missing or invalid. */
    private function day(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        try {
            $day = Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }

        return $day->gt(today()) ? today() : $day;
    }
}
