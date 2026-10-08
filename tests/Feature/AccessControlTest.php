<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    public function test_database_wiping_routes_do_not_exist(): void
    {
        foreach (['/migrate-fresh-seed', '/seed', '/run-artisan-clear-x7q9', '/refresh-storage-link'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_server_setup_needs_the_secret_token(): void
    {
        // Old maintenance URLs are gone; even a shop's super admin cannot run server tasks
        $owner = $this->userWithPermissions([], 'super_admin');
        $this->actingAs($owner)->get('/maintenance/migrate')->assertNotFound();

        $this->get('/setup')->assertNotFound();
        $this->get('/setup?token=wrong-token-0123456789')->assertNotFound();
        $this->post('/setup/migrate?token=wrong-token-0123456789')->assertNotFound();
        $this->get('/setup?token=test-setup-token-0123456789')->assertOk()->assertSee('Server setup');
    }

    public function test_user_without_a_role_cannot_reach_admin_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->post('/admin/users', [])->assertForbidden();
        $this->actingAs($user)->get('/settings')->assertForbidden();
        $this->actingAs($user)->get('/reports/profit-loss')->assertForbidden();
        $this->actingAs($user)->get('/products')->assertForbidden();
        $this->actingAs($user)->post('/pos/finalize', [])->assertForbidden();
    }

    public function test_cashier_can_sell_but_not_manage(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->get('/pos/register/open')->assertOk();

        $this->actingAs($cashier)->get('/admin/users')->assertForbidden();
        $this->actingAs($cashier)->get('/admin/roles')->assertForbidden();
        $this->actingAs($cashier)->get('/settings')->assertForbidden();
        $this->actingAs($cashier)->get('/loss-prevention')->assertForbidden();
        $this->actingAs($cashier)->get('/reports/profit-loss')->assertForbidden();

        // Deleting is refused (and the attempt is logged in Loss Prevention)
        $register = $this->openRegisterFor($cashier);
        $sale = \App\Models\Sale::create(['register_id' => $register->id, 'invoice_no' => 'INV-AC', 'payment_status' => 'paid',
            'payment_method' => 'cash', 'total_amount' => 100, 'total_paid' => 100, 'total_items' => 1]);
        $this->actingAs($cashier)->delete("/sales/{$sale->id}")->assertSessionHas('error');
        $this->assertNotNull(\App\Models\Sale::find($sale->id));
        $this->assertSame(1, \App\Models\IntegrityEvent::where('type', 'delete_blocked')->count());

        // No links to pages the cashier cannot open
        $this->actingAs($cashier)->get('/dashboard')->assertOk()
            ->assertDontSee('href="' . route('reports.low-stock') . '"', false)
            ->assertDontSee('href="' . route('reports.stock-expiry') . '"', false);
        $this->actingAs($cashier)->get('/sales')->assertOk()->assertDontSee(route('sales.edit', $sale->id));
        $this->actingAs($cashier)->get("/sales/{$sale->id}")->assertOk()->assertDontSee(route('sales.edit', $sale->id))->assertDontSee('confirmDelete(' . $sale->id . ')', false);
    }

    public function test_admin_role_reaches_admin_only_pages(): void
    {
        $admin = $this->userWithPermissions(['view_user'], 'admin');

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/settings')->assertOk();
    }
}
