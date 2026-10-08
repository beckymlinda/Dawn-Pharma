<?php

namespace Tests\Feature;

use App\Helpers\PaymentMethods;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class SettingsHubTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private function admin()
    {
        return $this->userWithPermissions(['add_pos_sell', 'view_pos_sell', 'view_user', 'view_role', 'access_all_expenses', 'add_expense', 'close_cash_register'], 'admin');
    }

    /** Turn methods on/off and rename them the way the settings form does (in this order) */
    private function saveMethods($user, array $rows)
    {
        return $this->actingAs($user)->post('/settings/payment-methods', ['methods' => $rows]);
    }

    private function defaultRows(array $overrides = []): array
    {
        return array_values(array_map(
            fn ($m) => array_merge(['key' => $m['key'], 'label' => $m['label'], 'enabled' => '1'], $overrides[$m['key']] ?? []),
            PaymentMethods::definitions()
        ));
    }

    public function test_settings_pages_share_tabs(): void
    {
        $admin = $this->admin();

        foreach (['/settings', '/settings/branding', '/settings/payment-methods', '/notifications', '/admin/users', '/admin/roles'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()
                ->assertSee('Shop details')->assertSee('Payment methods')->assertSee('Notifications');
        }
    }

    public function test_sidebar_has_settings_but_not_users_or_notifications(): void
    {
        $html = $this->actingAs($this->admin())->get('/dashboard')->assertOk()->getContent();
        $sidebar = substr($html, strpos($html, 'id="sidebar-wrapper"'), 12000);

        $this->assertStringContainsString('<span>Settings</span>', $sidebar);
        $this->assertStringNotContainsString('<span>Notifications</span>', $sidebar);
        $this->assertStringNotContainsString(route('admin.users.index'), $sidebar);
    }

    public function test_a_user_manager_who_is_not_admin_reaches_users_through_settings(): void
    {
        $manager = $this->userWithPermissions(['view_user'], 'manager');

        $html = $this->actingAs($manager)->get('/dashboard')->getContent();
        $this->assertStringContainsString('href="' . route('admin.users.index') . '"', $html);
        $this->actingAs($manager)->get('/admin/users')->assertOk()->assertSee('Users')->assertDontSee('Payment methods');
    }

    public function test_saving_shop_details_and_branding_does_not_touch_each_other(): void
    {
        $admin = $this->admin();
        Setting::create(['key' => 'primary_color', 'value' => '#123456']);

        $this->actingAs($admin)->post('/settings', ['section' => 'shop', 'system_name' => 'Kanengo Pharmacy', 'location' => 'Kanengo'])
            ->assertSessionHasNoErrors();
        $this->assertSame('#123456', Setting::where('key', 'primary_color')->value('value'));

        // Branding needs no shop name
        $this->actingAs($admin)->post('/settings', ['section' => 'branding', 'primary_color' => '#654321', 'secondary_color' => '#111111'])
            ->assertSessionHasNoErrors();
        $this->assertSame('#654321', Setting::where('key', 'primary_color')->value('value'));
        $this->assertSame('Kanengo Pharmacy', Setting::where('key', 'system_name')->value('value'));
    }

    public function test_switched_off_methods_are_not_offered_at_the_till_or_on_expenses(): void
    {
        $admin = $this->admin();
        $this->saveMethods($admin, $this->defaultRows(['airtel_money' => ['enabled' => null], 'mpamba' => ['label' => 'Mpamba']]))
            ->assertSessionHasNoErrors();

        $this->openRegisterFor($admin);
        $pos = $this->actingAs($admin)->get('/pos/new')->assertOk()->getContent();
        preg_match('/window\.POS_PAYMENT_METHODS = (.*?);<\/script>/', $pos, $m);
        $keys = array_column(json_decode($m[1], true), 'label', 'key');
        $this->assertArrayNotHasKey('airtel_money', $keys);
        $this->assertSame('Mpamba', $keys['mpamba']);

        $this->actingAs($admin)->get('/expenses')->assertOk()
            ->assertDontSee('<option value="airtel_money"', false)
            ->assertSee('<option value="mpamba"', false);

        // Reports still name it for old sales
        $this->assertSame('Airtel Money', PaymentMethods::getDisplayNames()['airtel_money']);
    }

    public function test_cash_cannot_be_switched_off(): void
    {
        $this->saveMethods($this->admin(), $this->defaultRows(['cash' => ['enabled' => null]]));

        $this->assertTrue(PaymentMethods::definitions()['cash']['enabled']);
    }

    public function test_order_is_saved(): void
    {
        $rows = $this->defaultRows();
        $card = array_values(array_filter($rows, fn ($r) => $r['key'] === 'card'));
        $rows = array_merge($card, array_values(array_filter($rows, fn ($r) => $r['key'] !== 'card')));
        $this->saveMethods($this->admin(), $rows);

        $this->assertSame('card', array_key_first(PaymentMethods::definitions()));
    }

    public function test_shop_adds_and_deletes_its_own_method(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/settings/payment-methods/add', ['label' => 'FDH Bank'])->assertSessionHasNoErrors();
        $this->assertSame('FDH Bank', PaymentMethods::definitions()['fdh_bank']['label'] ?? null);
        $this->actingAs($admin)->post('/settings/payment-methods/add', ['label' => 'fdh bank'])->assertSessionHasErrors('label');

        $this->openRegisterFor($admin);
        $pos = $this->actingAs($admin)->get('/pos/new')->assertOk()->getContent();
        $this->assertStringContainsString('"key":"fdh_bank"', $pos);

        $this->actingAs($admin)->delete('/settings/payment-methods/fdh_bank')->assertSessionHas('success');
        $this->assertArrayNotHasKey('fdh_bank', PaymentMethods::definitions());

        // Built-in methods can only be switched off
        $this->actingAs($admin)->delete('/settings/payment-methods/card')->assertSessionHas('error');
        $this->assertArrayHasKey('card', PaymentMethods::definitions());
    }

    public function test_a_used_method_cannot_be_deleted_and_shows_on_the_close_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/settings/payment-methods/add', ['label' => 'Insurance']);
        $register = $this->openRegisterFor($admin);
        Sale::create(['register_id' => $register->id, 'invoice_no' => 'T-1', 'payment_status' => 'paid', 'payment_method' => 'insurance',
            'total_amount' => 7000, 'total_paid' => 7000, 'total_items' => 1]);

        $this->actingAs($admin)->delete('/settings/payment-methods/insurance')->assertSessionHas('error');
        $this->assertArrayHasKey('insurance', PaymentMethods::definitions());

        $this->actingAs($admin)->get('/pos/register/close')->assertOk()
            ->assertSee('Insurance')->assertSee('7,000.00');
    }

    public function test_each_shop_has_its_own_methods(): void
    {
        $this->saveMethods($this->admin(), $this->defaultRows(['card' => ['enabled' => null]]));
        $this->assertFalse(PaymentMethods::definitions()['card']['enabled']);

        $other = Tenant::create(['name' => 'Other Shop', 'slug' => 'other-shop']);
        with_tenant($other, function () {
            PaymentMethods::flush();
            $this->assertTrue(PaymentMethods::definitions()['card']['enabled']);
        });
    }

    public function test_cashiers_cannot_change_payment_methods(): void
    {
        $cashier = $this->userWithPermissions(['add_pos_sell'], 'cashier');

        $this->actingAs($cashier)->get('/settings/payment-methods')->assertForbidden();
        $this->actingAs($cashier)->post('/settings/payment-methods/add', ['label' => 'Sneaky'])->assertForbidden();
        $this->assertArrayNotHasKey('sneaky', PaymentMethods::definitions());
    }
}
