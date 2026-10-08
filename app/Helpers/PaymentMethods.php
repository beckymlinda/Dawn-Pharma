<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Payment methods a shop accepts. Each shop can rename them, switch them on or
 * off, put them in order and add its own (Settings → Payment methods); the list
 * is stored in the "payment_methods" setting. Only methods that are on are
 * offered at the till and on forms; reports still name every method, so older
 * sales paid with a method that has since been switched off still show.
 */
class PaymentMethods
{
    public const SETTING = 'payment_methods';

    /** Cash cannot be switched off: the register's cash drawer depends on it */
    public const ALWAYS_ON = 'cash';

    /** Built-in methods, in their default order */
    public const BUILT_IN = [
        'cash' => ['label' => 'Cash', 'icon' => 'bi-cash-stack', 'color' => '#059669'],
        'airtel_money' => ['label' => 'Airtel Money', 'icon' => 'bi-phone', 'color' => '#dc2626'],
        'mpamba' => ['label' => 'TNM Mpamba', 'icon' => 'bi-phone-fill', 'color' => '#16a34a'],
        'card' => ['label' => 'Card', 'icon' => 'bi-credit-card', 'color' => '#2563eb'],
        'bank_transfer' => ['label' => 'Bank Transfer', 'icon' => 'bi-bank', 'color' => '#0891b2'],
        'national_bank' => ['label' => 'National Bank', 'icon' => 'bi-bank2', 'color' => '#4f46e5'],
        'standard_bank' => ['label' => 'Standard Bank', 'icon' => 'bi-building', 'color' => '#1d4ed8'],
        'cheque' => ['label' => 'Cheque', 'icon' => 'bi-journal-text', 'color' => '#64748b'],
        'advance' => ['label' => 'Advance', 'icon' => 'bi-wallet2', 'color' => '#d97706'],
    ];

    /** Look of methods a shop adds itself */
    private const CUSTOM_ICON = 'bi-wallet';
    private const CUSTOM_COLOR = '#7c3aed';

    /** Per-request cache (lives in the app container, so it never outlives a request or test) */
    private static function cache(): \ArrayObject
    {
        if (!app()->bound('payment_methods.cache')) {
            app()->instance('payment_methods.cache', new \ArrayObject());
        }

        return app('payment_methods.cache');
    }

    /**
     * Every method of this shop, in its order:
     * [key => ['key', 'label', 'icon', 'color', 'enabled', 'builtin']]
     */
    public static function definitions(): array
    {
        $cacheKey = (string) (function_exists('tenant') && tenant() ? tenant()->id : 'none');
        if (isset(self::cache()[$cacheKey])) {
            return self::cache()[$cacheKey];
        }

        $saved = json_decode((string) Setting::where('key', self::SETTING)->value('value'), true);
        $saved = is_array($saved) ? $saved : [];

        $list = [];
        foreach ($saved as $row) {
            $key = $row['key'] ?? null;
            if (!is_string($key) || $key === '' || isset($list[$key])) {
                continue;
            }
            $builtin = self::BUILT_IN[$key] ?? null;
            $list[$key] = [
                'key' => $key,
                'label' => trim((string) ($row['label'] ?? '')) ?: ($builtin['label'] ?? self::labelFromKey($key)),
                'icon' => $builtin['icon'] ?? self::CUSTOM_ICON,
                'color' => $builtin['color'] ?? self::CUSTOM_COLOR,
                'enabled' => $key === self::ALWAYS_ON || (bool) ($row['enabled'] ?? true),
                'builtin' => (bool) $builtin,
            ];
        }

        // Built-in methods the saved list doesn't mention (none saved yet, or added in an update)
        foreach (self::BUILT_IN as $key => $builtin) {
            $list[$key] ??= $builtin + ['key' => $key, 'enabled' => true, 'builtin' => true];
        }

        self::cache()[$cacheKey] = $list;

        return $list;
    }

    /** Methods that are switched on, in order (what the till and forms offer) */
    public static function enabled(): array
    {
        return array_filter(self::definitions(), fn ($m) => $m['enabled']);
    }

    /** key => label of the methods that are on, plus $current so an old record keeps its value */
    public static function enabledNames(?string $current = null): array
    {
        $names = array_map(fn ($m) => $m['label'], self::enabled());
        if ($current && !isset($names[$current])) {
            $names[$current] = self::getDisplayNames()[$current] ?? self::labelFromKey($current);
        }

        return $names;
    }

    /** What the POS payment screen needs */
    public static function forPos(): array
    {
        return array_values(array_map(fn ($m) => ['key' => $m['key'], 'label' => $m['label'], 'icon' => $m['icon']], self::enabled()));
    }

    /**
     * Save the shop's list: rows of ['key', 'label', 'enabled'] in the wanted order.
     */
    public static function save(array $rows): void
    {
        $clean = [];
        foreach ($rows as $row) {
            $key = $row['key'];
            $clean[] = [
                'key' => $key,
                'label' => mb_substr(trim((string) $row['label']), 0, 40),
                'enabled' => $key === self::ALWAYS_ON ? true : (bool) $row['enabled'],
            ];
        }
        Setting::updateOrCreate(['key' => self::SETTING], ['value' => json_encode($clean)]);
        self::flush();
    }

    /** A key for a new method from its name ("FDH Bank" → "fdh_bank"), unique in this shop */
    public static function keyFor(string $label): string
    {
        $base = Str::limit(Str::slug($label, '_'), 30, '') ?: 'method';
        $taken = array_merge(array_keys(self::definitions()), ['split', 'other', 'cheques', 'cash_and_card']);
        $key = $base;
        for ($i = 2; in_array($key, $taken, true); $i++) {
            $key = $base . '_' . $i;
        }

        return $key;
    }

    public static function flush(): void
    {
        self::cache()->exchangeArray([]);
    }

    private static function labelFromKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', strtolower($key)));
    }

    /**
     * Get all available payment methods (every key a payment can carry, on or off)
     */
    public static function getAllMethods()
    {
        return array_merge(array_keys(self::definitions()), ['other']);
    }

    /**
     * Get payment method display names (every method, on or off, for reports and history)
     */
    public static function getDisplayNames()
    {
        return array_map(fn ($m) => $m['label'], self::definitions()) + ['other' => 'Other'];
    }

    /**
     * Initialize payment method totals array
     */
    public static function initializeTotals()
    {
        $methods = self::getAllMethods();
        $totals = [];

        foreach ($methods as $method) {
            $totals[$method] = 0;
        }

        return $totals;
    }

    /**
     * Calculate payment method totals from sales collection
     */
    public static function calculateTotals($sales)
    {
        $totals = self::initializeTotals();

        foreach ($sales as $sale) {
            if ($sale->payment_method === 'split' && $sale->payments) {
                foreach ($sale->payments as $payment) {
                    $method = strtolower($payment->method);
                    if (isset($totals[$method])) {
                        $totals[$method] += $payment->amount;
                    } else {
                        $totals['other'] += $payment->amount;
                    }
                }
            } else {
                $method = strtolower($sale->payment_method);

                if (isset($totals[$method])) {
                    $totals[$method] += $sale->total_amount;
                } else {
                    $totals['other'] += $sale->total_amount;
                }
            }
        }

        return $totals;
    }
}
