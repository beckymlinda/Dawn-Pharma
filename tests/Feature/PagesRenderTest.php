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

    public function test_every_page_for_a_record_renders_for_super_admin(): void
    {
        $admin = $this->superAdmin();
        $register = $this->openRegisterFor($admin);
        $product = $this->product();
        $supplier = \App\Models\Contact::create(['name' => 'Supplier', 'type' => 'supplier']);
        $sale = \App\Models\Sale::create(['register_id' => $register->id, 'user_id' => $admin->id, 'invoice_no' => 'INV-R', 'payment_status' => 'paid',
            'payment_method' => 'cash', 'total_amount' => 1000, 'total_paid' => 1000, 'total_items' => 1]);
        \App\Models\SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 1000, 'subtotal' => 1000, 'purchase_cost' => 600]);
        $purchase = \App\Models\Purchase::create(['supplier_id' => $supplier->id, 'business_location' => 'Main', 'reference_no' => 'PO-1',
            'purchase_date' => now()->toDateString(), 'status' => 'received', 'payment_status' => 'paid', 'grand_total' => 600, 'paid_amount' => 600, 'created_by' => $admin->id]);
        \App\Models\PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 600, 'line_total' => 600]);
        \App\Models\StockHistory::create(['product_id' => $product->id, 'type' => 'Purchase', 'quantity_change' => 1, 'new_quantity' => 11, 'date' => now()->toDateString()]);
        $ids = [
            'product' => $product->id, 'productId' => $product->id, 'sale' => $sale->id, 'purchase' => $purchase->id, 'id' => $register->id,
            'category' => \App\Models\Category::create(['name' => 'Cat'])->id, 'brand' => \App\Models\Brand::create(['name' => 'Brand'])->id,
            'unit' => \App\Models\Unit::create(['name' => 'Box', 'short_name' => 'bx'])->id, 'user' => $admin->id, 'role' => Role::first()->id,
            'stockAdjustment' => \App\Models\StockAdjustment::create(['product_id' => $product->id, 'user_id' => $admin->id, 'adjustment_type' => 'add',
                'quantity' => 1, 'previous_quantity' => 10, 'new_quantity' => 11, 'previous_price' => 1000, 'new_price' => 1000, 'reason' => 'r'])->id,
            'event' => \App\Models\IntegrityEvent::create(['type' => 'sale_deleted', 'severity' => 'high', 'user_id' => $admin->id, 'description' => 'x'])->id,
            'activityLog' => \App\Models\ActivityLog::create(['user_id' => $admin->id, 'action' => 'login', 'description' => 'x'])->id,
        ];

        $failures = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            preg_match_all('/\{(\w+)\??\}/', $uri, $params);
            if (!in_array('GET', $route->methods()) || !$params[1] || !in_array('auth', $route->gatherMiddleware())
                || str_starts_with($uri, 'platform') || array_diff($params[1], array_keys($ids))) {
                continue;
            }
            $url = preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $ids[$m[1]], $uri);
            $status = $this->actingAs($admin)->get('/' . $url)->getStatusCode();
            if ($status >= 500 || $status === 404) {
                $failures[] = "{$status} /{$url}";
            }
        }

        $this->assertSame([], $failures, 'Record pages failed to render');
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
