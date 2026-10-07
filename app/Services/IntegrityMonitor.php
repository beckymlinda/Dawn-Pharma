<?php

namespace App\Services;

use App\Models\IntegrityEvent;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

/**
 * Loss-prevention checks, called from the points in the POS where theft
 * typically happens. Every method is fail-safe: a monitoring error must
 * never stop a sale from going through.
 */
class IntegrityMonitor
{
    /**
     * Snapshot of a sale (items + payments) so it survives deletion or edits.
     */
    public static function snapshot(Sale $sale): array
    {
        $sale->loadMissing(['items.product', 'payments']);

        return [
            'invoice_no' => $sale->invoice_no,
            'customer_name' => $sale->customer_name,
            'payment_method' => $sale->payment_method,
            'total_amount' => (float) $sale->total_amount,
            'created_at' => optional($sale->created_at)->toDateTimeString(),
            'items' => $sale->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'name' => $i->product->name ?? 'Product #' . $i->product_id,
                'quantity' => (float) $i->quantity,
                'price' => (float) $i->price,
                'subtotal' => (float) $i->subtotal,
            ])->values()->all(),
            'payments' => $sale->payments->map(fn ($p) => [
                'method' => $p->method,
                'amount' => (float) $p->amount,
            ])->values()->all(),
        ];
    }

    /**
     * Checks run on every completed POS sale: price overrides, below-cost
     * sales and sales outside business hours.
     */
    public static function checkCompletedSale(Sale $sale, ?array $onlyProductIds = null): void
    {
        try {
            $sale->loadMissing('items');
            $products = Product::whereIn('id', $sale->items->pluck('product_id'))->get()->keyBy('id');
            $base = ['sale_id' => $sale->id, 'invoice_no' => $sale->invoice_no, 'register_id' => $sale->register_id];

            foreach ($sale->items as $item) {
                if ($onlyProductIds !== null && !in_array($item->product_id, $onlyProductIds)) {
                    continue;
                }
                $product = $products[$item->product_id] ?? null;
                if (!$product) {
                    continue;
                }

                $price = (float) $item->price;
                $listPrice = (float) $product->selling_price;
                $cost = (float) ($item->purchase_cost ?? $product->purchase_price);
                $qty = (float) $item->quantity;
                $details = ['product' => $product->name, 'quantity' => $qty, 'charged_price' => $price, 'selling_price' => $listPrice, 'cost' => $cost];

                if ($listPrice > 0 && $price < $listPrice - 0.01) {
                    $loss = ($listPrice - $price) * $qty;
                    $pct = round(($listPrice - $price) / $listPrice * 100);
                    IntegrityEvent::record('price_override', $pct >= 20 ? 'critical' : 'high',
                        "{$product->name} sold at " . formatCurrency($price) . " instead of " . formatCurrency($listPrice) . " ({$pct}% less) on {$sale->invoice_no}",
                        $base + ['product_id' => $product->id, 'amount' => $loss, 'details' => $details]);
                }

                if ($cost > 0 && $price < $cost - 0.01) {
                    IntegrityEvent::record('below_cost', 'high',
                        "{$product->name} sold below cost (" . formatCurrency($price) . " vs cost " . formatCurrency($cost) . ") on {$sale->invoice_no}",
                        $base + ['product_id' => $product->id, 'amount' => ($cost - $price) * $qty, 'details' => $details]);
                }
            }

            // After-hours applies to new sales only, not to later edits
            if ($onlyProductIds === null && self::isAfterHours($sale->created_at ?? now())) {
                IntegrityEvent::record('after_hours_sale', 'medium',
                    "Sale {$sale->invoice_no} of " . formatCurrency($sale->total_amount) . ' made at ' . ($sale->created_at ?? now())->format('H:i')
                    . ' (business hours ' . IntegrityEvent::setting('lp_open_time') . '–' . IntegrityEvent::setting('lp_close_time') . ')',
                    $base + ['amount' => $sale->total_amount]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function isAfterHours($time): bool
    {
        $open = IntegrityEvent::setting('lp_open_time');
        $close = IntegrityEvent::setting('lp_close_time');
        $now = $time->format('H:i');

        // Supports overnight hours too (e.g. 20:00 to 06:00)
        return $open <= $close
            ? ($now < $open || $now >= $close)
            : ($now < $open && $now >= $close);
    }

    /**
     * Record a change to a completed sale, comparing server-side snapshots.
     */
    public static function recordEdit(Sale $sale, array $before, string $via, ?string $reason = null, bool $afterClose = false): void
    {
        try {
            $after = self::snapshot($sale->fresh());
            $change = $after['total_amount'] - $before['total_amount'];
            $methodChanged = $before['payment_method'] !== $after['payment_method'];
            $cashBefore = self::cashIn($before);
            $cashAfter = self::cashIn($after);

            // Lowering the total, moving money away from cash, or changing a closed shift is the risky direction
            $severity = $change < -0.01 || $cashAfter < $cashBefore - 0.01 || $afterClose ? 'high' : 'medium';

            $parts = [];
            if (abs($change) > 0.01) {
                $parts[] = 'total ' . formatCurrency($before['total_amount']) . ' → ' . formatCurrency($after['total_amount']);
            }
            if ($methodChanged) {
                $parts[] = "payment {$before['payment_method']} → {$after['payment_method']}";
            }
            // Item-level changes, e.g. "Paracetamol 2 → 1"
            $old = collect($before['items'])->groupBy('product_id');
            $new = collect($after['items'])->groupBy('product_id');
            foreach ($old->keys()->merge($new->keys())->unique() as $pid) {
                $oldQty = (float) ($old[$pid] ?? collect())->sum('quantity');
                $newQty = (float) ($new[$pid] ?? collect())->sum('quantity');
                $oldPrice = ($old[$pid] ?? collect())->first()['price'] ?? null;
                $newPrice = ($new[$pid] ?? collect())->first()['price'] ?? null;
                $name = (($old[$pid] ?? $new[$pid])->first()['name']) ?? "#{$pid}";
                $fmt = fn ($q) => rtrim(rtrim(number_format($q, 2), '0'), '.');
                if (abs($oldQty - $newQty) > 0.0001) {
                    $parts[] = "{$name} {$fmt($oldQty)} → {$fmt($newQty)}";
                } elseif ($oldPrice !== null && $newPrice !== null && abs($oldPrice - $newPrice) > 0.001) {
                    $parts[] = "{$name} price " . formatCurrency($oldPrice) . ' → ' . formatCurrency($newPrice);
                }
            }

            IntegrityEvent::record('sale_edited', $severity,
                "Sale {$sale->invoice_no} edited via {$via}" . ($afterClose ? ' after the shift was closed' : '') . ': '
                    . ($parts ? implode(', ', $parts) : 'details changed') . ($reason ? ". Reason: {$reason}" : ''),
                [
                    'sale_id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'register_id' => $sale->register_id,
                    'amount' => max(0, -$change, $cashBefore - $cashAfter),
                    'details' => ['before' => $before, 'after' => $after, 'reason' => $reason, 'after_close' => $afterClose],
                ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function cashIn(array $snapshot): float
    {
        if ($snapshot['payment_method'] !== 'split') {
            return $snapshot['payment_method'] === 'cash' ? $snapshot['total_amount'] : 0;
        }

        return collect($snapshot['payments'])->where('method', 'cash')->sum('amount');
    }

    /**
     * Cash the drawer should hold: opening float + cash sales + cash part of split payments.
     */
    public static function expectedCash(Register $register): float
    {
        $cashSales = DB::table('sales')->where('register_id', $register->id)->where('payment_method', 'cash')->sum('total_amount');
        $splitCash = DB::table('payments')
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.register_id', $register->id)
            ->where('sales.payment_method', 'split')
            ->where('payments.method', 'cash')
            ->sum('payments.amount');

        return (float) $register->opening_cash + (float) $cashSales + (float) $splitCash;
    }

    /**
     * Compare counted cash with expected cash at register close and flag differences.
     * Returns the attributes to save on the register.
     */
    public static function closeRegister(Register $register, $countedCash): array
    {
        $expected = self::expectedCash($register);

        if ($countedCash === null || $countedCash === '') {
            // Cash counting switched off in Loss Prevention settings: nothing to flag
            if (!IntegrityEvent::cashCountRequired()) {
                return ['expected_cash' => $expected];
            }
            IntegrityEvent::record('cash_not_counted', 'medium',
                "Register #{$register->id} closed without counting the cash (expected " . formatCurrency($expected) . ')',
                ['register_id' => $register->id, 'details' => ['expected_cash' => $expected]]);

            return ['expected_cash' => $expected];
        }

        $counted = (float) $countedCash;
        $variance = round($counted - $expected, 2);
        $tolerance = (float) IntegrityEvent::setting('lp_cash_tolerance');
        $details = ['expected_cash' => $expected, 'counted_cash' => $counted, 'variance' => $variance, 'tolerance' => $tolerance];

        if ($variance < -$tolerance) {
            IntegrityEvent::record('cash_shortage', abs($variance) >= $tolerance * 10 ? 'critical' : 'high',
                "Register #{$register->id} is short by " . formatCurrency(abs($variance)) . ' (counted ' . formatCurrency($counted) . ', expected ' . formatCurrency($expected) . ')',
                ['register_id' => $register->id, 'amount' => abs($variance), 'details' => $details]);
        } elseif ($variance > $tolerance) {
            IntegrityEvent::record('cash_surplus', 'medium',
                "Register #{$register->id} has " . formatCurrency($variance) . ' more cash than expected (counted ' . formatCurrency($counted) . ', expected ' . formatCurrency($expected) . ')',
                ['register_id' => $register->id, 'amount' => $variance, 'details' => $details]);
        }

        return ['expected_cash' => $expected, 'counted_cash' => $counted, 'cash_variance' => $variance];
    }

    /**
     * Items removed from the cart before payment (voids).
     */
    public static function recordCartRemoval(array $item, float $removedQty, string $how): void
    {
        $amount = (float) ($item['price'] ?? 0) * $removedQty;
        IntegrityEvent::record('line_void', 'low',
            ($how === 'removed' ? 'Removed ' : 'Reduced quantity of ') . ($item['name'] ?? 'item') . " by {$removedQty} from the cart (" . formatCurrency($amount) . ')',
            ['product_id' => $item['product_id'] ?? null, 'amount' => $amount, 'details' => ['item' => $item, 'removed_quantity' => $removedQty]]);
    }

    public static function recordCartCleared(array $cart, ?string $reason = null): void
    {
        if (empty($cart)) {
            return;
        }
        $total = collect($cart)->sum('subtotal');
        IntegrityEvent::record('cart_cleared', 'medium',
            'Cart with ' . count($cart) . ' item(s) worth ' . formatCurrency($total) . ' was cleared without a sale' . ($reason ? " ({$reason})" : ''),
            ['amount' => $total, 'details' => ['items' => array_values($cart), 'reason' => $reason]]);
    }
}
