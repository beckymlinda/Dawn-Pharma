<?php

namespace App\Services;

use App\Helpers\PaymentMethods;
use App\Models\IntegrityEvent;
use App\Models\Payment;
use App\Models\POS;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single place where a completed sale is changed. Keeps the sale,
 * its payments, stock, stock history, the register total and the
 * Loss Prevention log consistent with each other.
 */
class SaleEditor
{
    /** Roles that may edit any sale (including closed shifts) and change prices. */
    public const SUPERVISOR_ROLES = ['super_admin', 'admin', 'manager'];

    public static function isSupervisor(User $user): bool
    {
        return $user->roles()->whereIn('name', self::SUPERVISOR_ROLES)->exists();
    }

    /**
     * Why this user may not edit this sale, or null if they may.
     */
    public static function denialReason(Sale $sale, User $user): ?string
    {
        if (!$user->hasPermission('edit_pos_sell')) {
            return 'You do not have permission to edit sales.';
        }
        if (self::isSupervisor($user)) {
            return null;
        }

        $register = $sale->register;
        if (!$register || (int) $register->user_id !== (int) $user->id) {
            return 'You can only edit sales made on your own register. Ask a manager.';
        }
        if ($register->closed_at) {
            return 'This shift is already closed, so the sale can no longer be edited. Ask a manager.';
        }

        return null;
    }

    /**
     * The unit price each product should be charged at when a non-supervisor edits:
     * what the customer originally paid for items already on the sale, otherwise the list price.
     */
    public static function lockedPrices(Sale $sale): array
    {
        return $sale->items->groupBy('product_id')->map(fn ($rows) => (float) $rows->first()->price)->all();
    }

    /**
     * Apply an edit.
     *
     * @param array $items    [['product_id' => int, 'quantity' => float, 'price' => float], ...]
     * @param array $payments [['method' => string, 'amount' => float], ...]
     */
    public function update(Sale $sale, array $items, array $payments, string $reason, string $via): Sale
    {
        $user = auth()->user();
        $sale->load(['items', 'payments', 'register']);

        if ($denied = self::denialReason($sale, $user)) {
            IntegrityEvent::record('edit_blocked', 'high',
                "Tried to edit sale {$sale->invoice_no} (" . formatCurrency($sale->total_amount) . "): {$denied}",
                ['sale_id' => $sale->id, 'invoice_no' => $sale->invoice_no, 'amount' => $sale->total_amount]);

            throw ValidationException::withMessages(['sale' => $denied]);
        }

        $supervisor = self::isSupervisor($user);
        $lockedPrices = self::lockedPrices($sale);
        $originalQty = $sale->items->groupBy('product_id')->map->sum('quantity');
        $originalCost = $sale->items->groupBy('product_id')->map(fn ($rows) => $rows->first()->purchase_cost);

        // Merge duplicate rows of the same product and apply price rules
        $lines = [];
        foreach ($items as $row) {
            $pid = (int) $row['product_id'];
            $qty = round((float) $row['quantity'], 2);
            if ($qty <= 0) {
                continue;
            }
            $product = Product::find($pid);
            if (!$product) {
                throw ValidationException::withMessages(['items' => "Product #{$pid} no longer exists."]);
            }
            $price = $supervisor
                ? round((float) $row['price'], 2)
                : (float) ($lockedPrices[$pid] ?? $product->selling_price);

            if (isset($lines[$pid])) {
                $lines[$pid]['quantity'] += $qty;
            } else {
                $lines[$pid] = ['product' => $product, 'quantity' => $qty, 'price' => $price];
            }
        }

        if (empty($lines)) {
            throw ValidationException::withMessages(['items' => 'A sale needs at least one item. To cancel the whole sale, a manager must delete it.']);
        }

        $newTotal = round(collect($lines)->sum(fn ($l) => $l['quantity'] * $l['price']), 2);

        // A single payment with no amount means "the customer pays the whole new total"
        if (count($payments) === 1 && (($payments[0]['amount'] ?? null) === null)) {
            $payments[0]['amount'] = $newTotal;
        }

        // Payments must cover exactly the new total
        $validMethods = PaymentMethods::getAllMethods();
        $payments = collect($payments)
            ->map(fn ($p) => ['method' => strtolower(trim($p['method'])), 'amount' => round((float) $p['amount'], 2)])
            ->filter(fn ($p) => $p['amount'] > 0)
            ->values();
        if ($payments->isEmpty() || $payments->contains(fn ($p) => !in_array($p['method'], $validMethods, true))) {
            throw ValidationException::withMessages(['payments' => 'Enter at least one valid payment.']);
        }
        if (abs($payments->sum('amount') - $newTotal) > 0.01) {
            throw ValidationException::withMessages(['payments' => 'Payments (' . formatCurrency($payments->sum('amount')) . ') must add up to the new total (' . formatCurrency($newTotal) . ').']);
        }

        // Reject edits that change nothing
        $before = IntegrityMonitor::snapshot($sale);
        $sameItems = collect($before['items'])->map(fn ($i) => [$i['product_id'], (float) $i['quantity'], (float) $i['price']])->sort()->values()->all()
            == collect($lines)->map(fn ($l, $pid) => [$pid, (float) $l['quantity'], (float) $l['price']])->sort()->values()->all();
        $samePayments = collect($before['payments'])->map(fn ($p) => [$p['method'], (float) $p['amount']])->sort()->values()->all()
            == $payments->map(fn ($p) => [$p['method'], $p['amount']])->sort()->values()->all();
        if ($sameItems && $samePayments) {
            throw ValidationException::withMessages(['items' => 'Nothing was changed.']);
        }

        // Enough stock for any increase?
        foreach ($lines as $pid => $line) {
            $extra = $line['quantity'] - (float) ($originalQty[$pid] ?? 0);
            if ($extra > 0 && (float) $line['product']->current_stock < $extra) {
                throw ValidationException::withMessages(['items' => "Not enough {$line['product']->name} in stock: {$line['product']->current_stock} available, {$extra} more needed."]);
            }
        }

        $closedShift = (bool) optional($sale->register)->closed_at;
        $paymentMethod = $payments->count() > 1 ? 'split' : $payments[0]['method'];

        DB::transaction(function () use ($sale, $lines, $payments, $originalQty, $originalCost, $newTotal, $paymentMethod, $before, $reason, $via, $user, $closedShift) {
            // 1. Stock: apply only the net change per product, with a movement record
            $productIds = collect(array_keys($lines))->merge($originalQty->keys())->unique();
            foreach ($productIds as $pid) {
                $oldQty = (float) ($originalQty[$pid] ?? 0);
                $newQty = (float) ($lines[$pid]['quantity'] ?? 0);
                $diff = $newQty - $oldQty;   // > 0 means more sold, stock goes down
                if (abs($diff) < 0.0001) {
                    continue;
                }
                $product = Product::lockForUpdate()->find($pid);
                if (!$product) {
                    continue;
                }
                $product->current_stock = (float) $product->current_stock - $diff;
                if (array_key_exists('stock', $product->getAttributes())) {
                    $product->stock = $product->current_stock;
                }
                $product->save();

                StockHistory::create([
                    'product_id' => $pid,
                    'type' => 'Sale',
                    'quantity_change' => -$diff,
                    'new_quantity' => $product->current_stock,
                    'unit_cost' => $product->purchase_price,
                    'unit_price' => $lines[$pid]['price'] ?? null,
                    'date' => now(),
                    'reference_no' => $sale->invoice_no,
                    'customer_supplier' => $sale->customer_name,
                    'notes' => "Sale edited: quantity {$oldQty} → {$newQty}. Reason: {$reason}",
                ]);
            }

            // 2. Items (keep the original cost so profit stays correct)
            $sale->items()->delete();
            foreach ($lines as $pid => $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $pid,
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'subtotal' => round($line['quantity'] * $line['price'], 2),
                    'purchase_cost' => $originalCost[$pid] ?? $line['product']->purchase_price,
                ]);
            }

            // 3. Payments
            $sale->payments()->delete();
            foreach ($payments as $p) {
                Payment::create(['sale_id' => $sale->id, 'method' => $p['method'], 'amount' => $p['amount']]);
            }

            // 4. The sale itself, with an edit history that keeps the very first version
            $previous = is_array($sale->edit_log) ? $sale->edit_log : (json_decode((string) $sale->edit_log, true) ?: []);
            $history = $previous['history'] ?? [];
            $history[] = [
                'at' => now()->toDateTimeString(),
                'by' => $user->name,
                'via' => $via,
                'reason' => $reason,
                'from_total' => $before['total_amount'],
                'to_total' => $newTotal,
            ];
            $sale->update([
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'total_amount' => $newTotal,
                'total_paid' => $newTotal,
                'sell_due' => 0,
                'total_items' => collect($lines)->sum('quantity'),
                'is_edited' => true,
                'edited_at' => now(),
                'edited_by' => $user->id,
                'edit_log' => [
                    'original_total' => $previous['original_total'] ?? $before['total_amount'],
                    'original_payment_method' => $previous['original_payment_method'] ?? $before['payment_method'],
                    'original_payments' => $previous['original_payments'] ?? $before['payments'],
                    'original_items' => $previous['original_items'] ?? collect($before['items'])->map(fn ($i) => $i + ['product_name' => $i['name']])->all(),
                    'new_total' => $newTotal,
                    'new_payment_method' => $paymentMethod,
                    'new_payments' => $payments->all(),
                    'new_items' => collect($lines)->map(fn ($l, $pid) => [
                        'product_id' => $pid, 'product_name' => $l['product']->name,
                        'quantity' => $l['quantity'], 'price' => $l['price'], 'subtotal' => round($l['quantity'] * $l['price'], 2),
                    ])->values()->all(),
                    'total_change' => $newTotal - ($previous['original_total'] ?? $before['total_amount']),
                    'reason' => $reason,
                    'edited_by' => $user->name,
                    'edited_at' => now()->toDateTimeString(),
                    'history' => $history,
                ],
            ]);

            // 5. Keep the POS list and a closed register's sales total in line
            POS::where('invoice_no', $sale->invoice_no)->update([
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'total_amount' => $newTotal,
                'total_paid' => $newTotal,
                'total_items' => collect($lines)->sum('quantity'),
            ]);
            if ($closedShift) {
                $sale->register->update(['closing_cash' => $sale->register->sales()->sum('total_amount')]);
            }
        });

        // 6. Loss Prevention
        $sale->refresh();
        IntegrityMonitor::recordEdit($sale, $before, $via, $reason, $closedShift);
        $changedPrices = collect($lines)->filter(fn ($l, $pid) => !isset($lockedPrices[$pid]) || abs($lockedPrices[$pid] - $l['price']) > 0.001)->keys()->all();
        if ($changedPrices) {
            IntegrityMonitor::checkCompletedSale($sale, $changedPrices);
        }

        return $sale;
    }
}
