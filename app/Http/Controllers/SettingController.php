<?php

namespace App\Http\Controllers;

use App\Helpers\PaymentMethods;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Settings hub: Shop details, Branding and Payment methods live here;
 * Notifications and Users & roles have their own pages under the same tabs.
 */
class SettingController extends Controller
{
    /** Shop details */
    public function index()
    {
        return $this->show('shop');
    }

    public function branding()
    {
        return $this->show('branding');
    }

    public function paymentMethods()
    {
        $methods = PaymentMethods::definitions();
        $used = $this->usedMethodKeys();

        return view('settings.payment-methods', compact('methods', 'used'));
    }

    private function show(string $tab)
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('settings.' . $tab, compact('settings'));
    }

    public function update(Request $request)
    {
        $section = $request->input('section', 'shop');

        if ($section === 'branding') {
            $request->validate([
                'primary_color'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico,webp|max:1024',
            ]);
            $keys = ['primary_color', 'secondary_color'];
        } else {
            $request->validate([
                'system_name' => 'required|string|max:255',
                'location'    => 'nullable|string|max:500',
                'telephone'   => 'nullable|string|max:50',
                'email'       => 'nullable|email|max:255',
                'tax_number'  => 'nullable|string|max:50',
                'receipt_footer' => 'nullable|string|max:255',
            ]);
            $keys = ['system_name', 'location', 'telephone', 'email', 'tax_number', 'receipt_footer'];
        }

        // Only this section's keys; other settings (loss prevention, payment methods…) have their own pages
        foreach ($request->only($keys) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($section === 'branding') {
            foreach (['logo', 'favicon'] as $image) {
                if ($request->hasFile($image)) {
                    $old = Setting::where('key', $image)->value('value');
                    if ($old) {
                        Storage::disk('public')->delete(str_replace('storage/', '', $old));
                    }
                    $path = $request->file($image)->store('settings', 'public');
                    Setting::updateOrCreate(['key' => $image], ['value' => 'storage/' . $path]);
                }
            }
        }

        return redirect()->back()->with('success', $section === 'branding' ? 'Branding saved.' : 'Shop details saved.');
    }

    /**
     * Save names, on/off and order of all methods.
     */
    public function updatePaymentMethods(Request $request)
    {
        $known = PaymentMethods::definitions();
        $request->validate([
            'methods' => 'required|array',
            'methods.*.key' => 'required|string',
            'methods.*.label' => 'required|string|max:40',
        ], [
            'methods.*.label.required' => 'Every payment method needs a name.',
        ]);

        $rows = [];
        foreach ($request->input('methods') as $row) {
            if (isset($known[$row['key']])) {
                $rows[] = ['key' => $row['key'], 'label' => $row['label'], 'enabled' => !empty($row['enabled'])];
            }
        }
        // Methods missing from the form (e.g. added in another tab) keep their place at the end
        foreach (array_diff_key($known, array_flip(array_column($rows, 'key'))) as $m) {
            $rows[] = ['key' => $m['key'], 'label' => $m['label'], 'enabled' => $m['enabled']];
        }

        PaymentMethods::save($rows);
        $on = count(array_filter($rows, fn ($r) => $r['enabled'] || $r['key'] === PaymentMethods::ALWAYS_ON));

        return back()->with('success', "Payment methods saved. {$on} " . ($on === 1 ? 'method is' : 'methods are') . ' offered at the till.');
    }

    public function storePaymentMethod(Request $request)
    {
        $request->validate(['label' => 'required|string|max:40'], ['label.required' => 'Give the new payment method a name.']);
        $label = trim($request->input('label'));

        $existing = array_map('mb_strtolower', array_column(PaymentMethods::definitions(), 'label'));
        if (in_array(mb_strtolower($label), $existing, true)) {
            return back()->withErrors(['label' => "There is already a payment method called {$label}."])->withInput();
        }

        $rows = array_values(array_map(fn ($m) => ['key' => $m['key'], 'label' => $m['label'], 'enabled' => $m['enabled']], PaymentMethods::definitions()));
        $rows[] = ['key' => PaymentMethods::keyFor($label), 'label' => $label, 'enabled' => true];
        PaymentMethods::save($rows);

        return back()->with('success', "{$label} added and switched on.");
    }

    /**
     * Remove a method the shop added. Built-in methods, and any method already
     * used on a sale or expense, can only be switched off (history keeps its name).
     */
    public function destroyPaymentMethod(string $key)
    {
        $methods = PaymentMethods::definitions();
        abort_unless(isset($methods[$key]), 404);

        if ($methods[$key]['builtin']) {
            return back()->with('error', 'Built-in payment methods cannot be deleted. Switch it off instead.');
        }
        if (in_array($key, $this->usedMethodKeys(), true)) {
            return back()->with('error', "{$methods[$key]['label']} has been used on sales or expenses, so it can only be switched off.");
        }

        $label = $methods[$key]['label'];
        unset($methods[$key]);
        PaymentMethods::save(array_values(array_map(fn ($m) => ['key' => $m['key'], 'label' => $m['label'], 'enabled' => $m['enabled']], $methods)));

        return back()->with('success', "{$label} deleted.");
    }

    /** Method keys that appear on this shop's sales, split payments or expenses */
    private function usedMethodKeys(): array
    {
        $tenantId = tenant()?->id;
        $scope = fn ($q, $table) => $tenantId ? $q->where("$table.tenant_id", $tenantId) : $q;

        return collect()
            ->merge($scope(DB::table('sales'), 'sales')->distinct()->pluck('payment_method'))
            ->merge($scope(DB::table('payments'), 'payments')->distinct()->pluck('method'))
            ->merge($scope(DB::table('expenses'), 'expenses')->whereNotNull('payment_method')->distinct()->pluck('payment_method'))
            ->map(fn ($k) => strtolower((string) $k))
            ->unique()->values()->all();
    }
}
