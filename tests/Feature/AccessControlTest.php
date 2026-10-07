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

    public function test_maintenance_routes_need_super_admin(): void
    {
        $this->get('/maintenance/migrate')->assertRedirect('/login');

        $admin = $this->userWithPermissions(['view_user'], 'admin');
        $this->actingAs($admin)->get('/maintenance/migrate')->assertForbidden();
        $this->actingAs($admin)->get('/maintenance/clear-cache')->assertForbidden();
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
        $this->actingAs($cashier)->delete('/sales/1')->assertForbidden();
    }

    public function test_admin_role_reaches_admin_only_pages(): void
    {
        $admin = $this->userWithPermissions(['view_user'], 'admin');

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/settings')->assertOk();
    }
}
