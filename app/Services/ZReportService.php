<?php

namespace App\Services;

use App\Helpers\PaymentMethods;
use App\Models\IntegrityEvent;
use App\Models\Register;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class ZReportService
{
    /**
     * Build all figures for a register's Z (end of shift) report.
     */
    public function build(Register $register): array
    {
        $register->loadMissing(['user', 'location']);
        $settings = Setting::pluck('value', 'key');

        $sales = DB::table('sales')->where('register_id', $register->id);

        $overall = (clone $sales)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(total_paid),0) as paid,
                         COALESCE(SUM(sell_due),0) as due, COALESCE(SUM(is_edited),0) as edited')
            ->first();

        // Payment breakdown: single-method sales plus each part of split payments
        $names = PaymentMethods::getDisplayNames();
        $payments = [];
        $label = fn (?string $method) => $names[strtolower($method ?: 'other')] ?? ucwords(str_replace('_', ' ', strtolower($method ?: 'other')));
        $addPayment = function (string $method, int $count, float $amount, bool $fromSplit = false) use (&$payments, $label) {
            $key = strtolower($method ?: 'other');
            $payments[$key] ??= ['label' => $label($key), 'count' => 0, 'amount' => 0.0, 'split_count' => 0, 'split_amount' => 0.0];
            $payments[$key]['count'] += $count;
            $payments[$key]['amount'] += $amount;
            if ($fromSplit) {
                $payments[$key]['split_count'] += $count;
                $payments[$key]['split_amount'] += $amount;
            }
        };

        (clone $sales)->where('payment_method', '!=', 'split')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->get()
            ->each(fn ($row) => $addPayment((string) $row->payment_method, (int) $row->count, (float) $row->total));

        // Each part of every split payment, so the report can show how a split sale was divided
        $splitParts = DB::table('payments')
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->where('sales.register_id', $register->id)
            ->where('sales.payment_method', 'split')
            ->orderBy('payments.id')
            ->get(['payments.sale_id', 'payments.method', 'payments.amount']);

        $splitParts->groupBy('method')
            ->each(fn ($rows, $method) => $addPayment((string) $method, $rows->count(), (float) $rows->sum('amount'), true));

        $splitBySale = $splitParts->groupBy('sale_id')->map(fn ($rows) => $rows->map(fn ($p) => [
            'label' => $label($p->method),
            'amount' => (float) $p->amount,
        ])->values()->all());

        uasort($payments, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        // Products sold during the shift
        $products = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.register_id', $register->id)
            ->selectRaw('sale_items.product_id, MAX(products.name) as name, MAX(products.sku) as sku,
                         SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as amount,
                         SUM(COALESCE(sale_items.purchase_cost, 0) * sale_items.quantity) as cost,
                         SUM(CASE WHEN sale_items.purchase_cost IS NULL THEN 1 ELSE 0 END) as missing_cost')
            ->groupBy('sale_items.product_id')
            ->orderByDesc('amount')
            ->get();

        $transactions = (clone $sales)
            ->orderBy('created_at')
            ->get(['id', 'invoice_no', 'customer_name', 'payment_method', 'total_amount', 'is_edited', 'created_at'])
            ->each(function ($t) use ($splitBySale, $label) {
                $t->split = $t->payment_method === 'split' ? ($splitBySale[$t->id] ?? []) : [];
                $t->payment_label = $t->split
                    ? 'Split: ' . collect($t->split)->map(fn ($p) => $p['label'] . ' ' . formatCurrency($p['amount']))->implode(' + ')
                    : $label($t->payment_method);
            });
        $splitSales = $transactions->filter(fn ($t) => $t->payment_method === 'split')->values();

        $grossSales = (float) $overall->total;
        $cost = (float) $products->sum('cost');
        $cashSales = $payments['cash']['amount'] ?? 0;
        $openingCash = (float) $register->opening_cash;
        $closedAt = $register->closed_at ?? now();

        return [
            'business' => $this->branding($settings),
            'register' => $register,
            'report_no' => 'Z-' . str_pad($register->id, 5, '0', STR_PAD_LEFT),
            'is_closed' => (bool) $register->closed_at,
            'cashier' => $register->user,
            'location' => $register->location->name ?? null,
            'opened_at' => $register->opened_at,
            'closed_at' => $closedAt,
            'duration' => $register->opened_at ? $this->formatDuration((int) $register->opened_at->diffInMinutes($closedAt)) : null,
            'sales_count' => (int) $overall->count,
            'gross_sales' => $grossSales,
            'total_paid' => (float) $overall->paid,
            'total_due' => (float) $overall->due,
            'edited_count' => (int) $overall->edited,
            'items_sold' => (float) $products->sum('qty'),
            'average_sale' => $overall->count ? $grossSales / $overall->count : 0,
            'cost_of_sales' => $cost,
            'gross_profit' => $grossSales - $cost,
            'profit_incomplete' => $products->sum('missing_cost') > 0,
            'payments' => $payments,
            'opening_cash' => $openingCash,
            'cash_sales' => $cashSales,
            // Use the figure fixed at close, so later edits/deletions can't rewrite the shift's numbers
            'expected_cash' => $register->expected_cash !== null ? (float) $register->expected_cash : $openingCash + $cashSales,
            'counted_cash' => $register->counted_cash !== null ? (float) $register->counted_cash : null,
            'cash_variance' => $register->cash_variance !== null ? (float) $register->cash_variance : null,
            'integrity_events' => IntegrityEvent::where('register_id', $register->id)->orderBy('created_at')->get(),
            'products' => $products,
            'transactions' => $transactions,
            'split_sales' => $splitSales,
            'generated_at' => now(),
        ];
    }

    /**
     * Pharmacy name, colours and logo from System Settings, using the same
     * fallbacks as the rest of the app (layouts/app.blade.php).
     */
    private function branding($settings): array
    {
        $logo = !empty($settings['logo']) ? $settings['logo'] : 'assets/images/logo.jpeg';
        $logoPath = public_path($logo);

        return [
            'name' => !empty($settings['system_name']) ? $settings['system_name'] : 'CNC Health Care',
            'location' => $settings['location'] ?? null,
            'telephone' => $settings['telephone'] ?? null,
            'primary' => !empty($settings['primary_color']) ? $settings['primary_color'] : '#004d3d',
            'secondary' => !empty($settings['secondary_color']) ? $settings['secondary_color'] : '#006b54',
            // The file is embedded in the email; the URL is used for the in-browser preview
            'logo_path' => is_file($logoPath) ? $logoPath : null,
            'logo_url' => is_file($logoPath) ? asset($logo) : null,
        ];
    }

    private function formatDuration(int $minutes): string
    {
        return intdiv($minutes, 60) . 'h ' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT) . 'm';
    }
}
