<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    // Pages that do something other than show a page (downloads, side effects)
    private const SKIP = [
        'maintenance/migrate', 'maintenance/clear-cache', 'maintenance/storage-link',
        'sales/summary/test-payments',
    ];

    private function superAdmin(): User
    {
        $role = Role::create(['name' => 'super_admin', 'display_name' => 'Super Admin', 'is_superadmin' => true]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_every_page_without_parameters_renders_for_super_admin(): void
    {
        Setting::create(['key' => 'system_name', 'value' => 'Smoke Test Shop']);
        $admin = $this->superAdmin();
        $this->openRegisterFor($admin);
        $this->product();

        $failures = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!in_array('GET', $route->methods()) || str_contains($uri, '{')
                || !in_array('auth', $route->gatherMiddleware()) || in_array($uri, self::SKIP)
                || str_starts_with($uri, 'platform')) {   // admin portal: only on the admin host (TenancyTest)
                continue;
            }

            $status = $this->actingAs($admin)->get('/' . ltrim($uri, '/'))->getStatusCode();
            if ($status >= 500 || $status === 404) {
                $failures[] = "{$status} /{$uri}";
            }
        }

        $this->assertSame([], $failures, 'Pages failed to render');
    }

    public function test_loss_prevention_page_keeps_branding_and_its_own_settings(): void
    {
        Setting::create(['key' => 'system_name', 'value' => 'Smoke Test Shop']);
        $admin = $this->superAdmin();

        $html = $this->actingAs($admin)->get('/loss-prevention')->assertOk()->getContent();

        $this->assertStringContainsString('Smoke Test Shop', $html);
        $this->assertStringContainsString('name="lp_cash_tolerance"', $html);
    }
}
