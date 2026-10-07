<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    public function test_defaults_come_from_config_until_settings_are_saved(): void
    {
        config(['business.system_name' => 'Default Shop']);

        $this->assertSame('Default Shop', business_settings(true)['system_name']);

        Setting::create(['key' => 'system_name', 'value' => 'Area 25 Pharmacy']);

        $this->assertSame('Area 25 Pharmacy', business_settings(true)['system_name']);
    }

    public function test_blank_saved_value_falls_back_to_default(): void
    {
        config(['business.receipt_footer' => 'Thanks!']);
        Setting::create(['key' => 'receipt_footer', 'value' => '']);

        $this->assertSame('Thanks!', business_settings(true)['receipt_footer']);
    }

    public function test_pages_show_the_configured_business_name_not_another_clients(): void
    {
        Setting::create(['key' => 'system_name', 'value' => 'Area 25 Pharmacy']);
        $user = $this->userWithPermissions(['view_brand'], 'admin');

        $html = $this->actingAs($user)->get('/brands')->assertOk()->getContent();

        $this->assertStringContainsString('Brands - Area 25 Pharmacy', $html);
        $this->assertStringNotContainsString('EDUC Pharmacy', $html);
        $this->assertStringNotContainsString('CNC Health Care', $html);
    }

    public function test_admin_can_save_business_details(): void
    {
        $admin = $this->userWithPermissions([], 'admin');

        $this->actingAs($admin)->post('/settings', [
            'system_name' => 'Area 25 Pharmacy',
            'telephone' => '+265 888 000 000',
            'tax_number' => '12345678',
            'receipt_footer' => 'Get well soon',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'system_name', 'value' => 'Area 25 Pharmacy']);
        $this->assertDatabaseHas('settings', ['key' => 'tax_number', 'value' => '12345678']);
    }

    public function test_settings_form_cannot_overwrite_other_settings(): void
    {
        $admin = $this->userWithPermissions([], 'admin');

        $this->actingAs($admin)->post('/settings', [
            'system_name' => 'Area 25 Pharmacy',
            'integrity_void_threshold' => '999999',
        ]);

        $this->assertDatabaseMissing('settings', ['key' => 'integrity_void_threshold']);
    }

    public function test_pos_page_only_sends_receipt_details_to_the_browser(): void
    {
        Setting::create(['key' => 'system_name', 'value' => 'Area 25 Pharmacy']);
        Setting::create(['key' => 'integrity_secret_threshold', 'value' => '5000']);
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);

        $html = $this->actingAs($cashier)->get('/pos/create')->assertOk()->getContent();

        $this->assertStringContainsString('"system_name":"Area 25 Pharmacy"', $html);
        $this->assertStringNotContainsString('integrity_secret_threshold', $html);
    }
}
