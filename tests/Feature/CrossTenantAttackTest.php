<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\IntegrityEvent;
use App\Models\NotificationLog;
use App\Models\NotificationRecipient;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Register;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\SmsRecipient;
use App\Models\StockAdjustment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

/**
 * An owner of one shop (with every permission) tries to see or change another
 * shop's data through every page, link and form that takes an id.
 */
class CrossTenantAttackTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private const SECRET = 'ZZSECRETB';

    private Tenant $victim;
    private array $b = [];
    private User $attacker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->victim = app(TenantProvisioner::class)->create(
            ['name' => 'Victim Shop', 'slug' => 'victim', 'grace_days' => 7],
            ['name' => 'Victim Owner', 'email' => 'owner@victim.test', 'password' => 'secret-password']
        );

        $this->b = with_tenant($this->victim, function () {
            $user = User::where('email', 'owner@victim.test')->first();
            $category = Category::create(['name' => self::SECRET . ' Category']);
            $brand = Brand::create(['name' => self::SECRET . ' Brand']);
            $unit = Unit::create(['name' => self::SECRET . ' Unit', 'short_name' => 'zz']);
            $supplier = Contact::create(['name' => self::SECRET . ' Supplier', 'type' => 'supplier']);
            $product = Product::create(['name' => self::SECRET . ' Product', 'sku' => 'SECRET-SKU', 'business_location' => 'B',
                'purchase_price' => 100, 'selling_price' => 999, 'current_stock' => 50, 'product_type' => 'Single',
                'category_id' => $category->id, 'brand_id' => $brand->id, 'unit_id' => $unit->id, 'supplier_id' => $supplier->id]);
            $register = Register::create(['user_id' => $user->id, 'opened_at' => now(), 'opening_cash' => 0]);
            $sale = Sale::create(['register_id' => $register->id, 'user_id' => $user->id, 'invoice_no' => self::SECRET . '-INV',
                'customer_name' => self::SECRET . ' Customer', 'payment_status' => 'paid', 'payment_method' => 'cash',
                'total_amount' => 999, 'total_paid' => 999, 'total_items' => 1]);
            SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 999, 'subtotal' => 999, 'purchase_cost' => 100]);
            Payment::create(['sale_id' => $sale->id, 'method' => 'cash', 'amount' => 999]);
            $purchase = Purchase::create(['supplier_id' => $supplier->id, 'business_location' => 'B', 'reference_no' => self::SECRET . '-PO',
                'purchase_date' => now()->toDateString(), 'status' => 'received', 'payment_status' => 'paid', 'grand_total' => 500, 'paid_amount' => 500, 'created_by' => $user->id]);
            PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 100, 'line_total' => 500]);
            $expense = Expense::create(['expense_date' => now()->toDateString(), 'category' => 'Rent', 'description' => self::SECRET . ' Rent', 'amount' => 777, 'user_id' => $user->id]);
            $adjustment = StockAdjustment::create(['product_id' => $product->id, 'user_id' => $user->id, 'adjustment_type' => 'add', 'quantity' => 1,
                'previous_quantity' => 49, 'new_quantity' => 50, 'previous_price' => 999, 'new_price' => 999, 'reason' => self::SECRET . ' reason']);
            $event = IntegrityEvent::create(['type' => 'sale_deleted', 'severity' => 'high', 'user_id' => $user->id, 'description' => self::SECRET . ' event']);
            $log = ActivityLog::create(['user_id' => $user->id, 'action' => 'login', 'description' => self::SECRET . ' activity']);
            $recipient = NotificationRecipient::create(['email' => 'secret@victim.test', 'name' => self::SECRET]);
            $phone = SmsRecipient::create(['phone' => '+265999999999', 'name' => self::SECRET]);
            NotificationLog::create(['type' => 'z_report', 'recipients' => 'secret@victim.test', 'status' => 'sent']);
            Setting::updateOrCreate(['key' => 'system_name'], ['value' => self::SECRET . ' Pharmacy']);
            $role = Role::where('name', '!=', 'super_admin')->first();

            return compact('user', 'category', 'brand', 'unit', 'supplier', 'product', 'register', 'sale', 'purchase', 'expense', 'adjustment', 'event', 'log', 'recipient', 'phone', 'role');
        });
        set_tenant($this->tenant);

        // The attacker: owner of the test shop, every permission
        $role = Role::create(['name' => 'super_admin', 'display_name' => 'Super Admin', 'is_superadmin' => true]);
        $this->attacker = User::factory()->create(['name' => 'Attacker']);
        $this->attacker->roles()->attach($role);
        $this->openRegisterFor($this->attacker);
        $this->product(['name' => 'My Own Product']);
    }

    private function victimCounts(): array
    {
        return with_tenant($this->victim, fn () => [
            Product::count(), Sale::count(), Expense::count(), User::count(), Role::count(), Category::count(), Brand::count(),
            Unit::count(), Contact::count(), Purchase::count(), StockAdjustment::count(), IntegrityEvent::count(), ActivityLog::count(),
            NotificationRecipient::count(), SmsRecipient::count(), NotificationLog::count(), Register::whereNull('closed_at')->count(),
            (float) Product::first()->current_stock, (float) Product::first()->selling_price, IntegrityEvent::first()->reviewed,
            (float) Sale::first()->total_amount, User::first()->name, Setting::where('key', 'system_name')->value('value'),
        ]);
    }

    public function test_no_page_shows_another_shops_data(): void
    {
        $pages = ['/dashboard', '/products', '/products/create', '/sales', '/expenses', '/admin/users', '/admin/roles', '/categories',
            '/brands', '/units', '/purchases', '/purchases/create', '/stock-adjustments', '/stock-adjustments/create', '/activity-log',
            '/loss-prevention', '/notifications', '/settings', '/settings/payment-methods', '/reports/profit-loss',
            '/reports/register-report', '/stock-report', '/reports/products', '/reports/low-stock', '/reports/stock-expiry',
            '/pos/new', '/pos/recent-sales', '/pos/register/details', '/pos/register/close', '/billing', '/profile'];

        foreach ($pages as $url) {
            $response = $this->actingAs($this->attacker)->get($url);
            $this->assertLessThan(500, $response->status(), "$url crashed");
            $this->assertStringNotContainsString(self::SECRET, (string) $response->getContent(), "$url shows the other shop's data");
            $this->assertStringNotContainsString('SECRET-SKU', (string) $response->getContent(), "$url shows the other shop's SKU");
        }
    }

    public function test_no_link_or_form_reaches_another_shops_records(): void
    {
        $before = $this->victimCounts();
        $b = $this->b;

        $attempts = [
            ['get', "/products/{$b['product']->id}"], ['get', "/products/{$b['product']->id}/edit"], ['get', "/products/{$b['product']->id}/stock-info"],
            ['put', "/products/{$b['product']->id}", ['name' => 'hacked', 'selling_price' => 1, 'purchase_price' => 1]],
            ['delete', "/products/{$b['product']->id}"],
            ['get', "/reports/products/{$b['product']->id}"], ['get', "/stock-history/{$b['product']->id}"],
            ['get', "/sales/{$b['sale']->id}"], ['get', "/sales/{$b['sale']->id}/edit"], ['get', "/pos/sale/{$b['sale']->id}/edit-data"],
            ['put', "/sales/{$b['sale']->id}", ['items' => [['product_id' => $b['product']->id, 'quantity' => 1, 'price' => 1]], 'payments' => [['method' => 'cash', 'amount' => 1]], 'reason' => 'x']],
            ['delete', "/sales/{$b['sale']->id}", ['reason' => 'x']],
            ['get', "/pos/register/{$b['register']->id}/details"], ['patch', "/pos/register/{$b['register']->id}", ['counted_cash' => 0]],
            ['get', "/purchases/{$b['purchase']->id}"], ['get', "/purchases/{$b['purchase']->id}/edit"], ['delete', "/purchases/{$b['purchase']->id}"],
            ['delete', "/expenses/{$b['expense']->id}"],
            ['get', "/admin/users/{$b['user']->id}/edit"], ['put', "/admin/users/{$b['user']->id}", ['name' => 'hacked', 'email' => 'owner@victim.test']],
            ['delete', "/admin/users/{$b['user']->id}"],
            ['get', "/admin/roles/{$b['role']->id}/edit"], ['put', "/admin/roles/{$b['role']->id}", ['name' => 'hacked', 'display_name' => 'hacked']],
            ['delete', "/admin/roles/{$b['role']->id}"],
            ['get', "/categories/{$b['category']->id}/edit"], ['put', "/categories/{$b['category']->id}", ['name' => 'hacked']], ['delete', "/categories/{$b['category']->id}"],
            ['get', "/brands/{$b['brand']->id}/edit"], ['put', "/brands/{$b['brand']->id}", ['name' => 'hacked']], ['delete', "/brands/{$b['brand']->id}"],
            ['get', "/units/{$b['unit']->id}/edit"], ['put', "/units/{$b['unit']->id}", ['name' => 'hacked', 'short_name' => 'h']], ['delete', "/units/{$b['unit']->id}"],
            ['get', "/stock-adjustments/{$b['adjustment']->id}"],
            ['get', "/loss-prevention/{$b['event']->id}"], ['patch', "/loss-prevention/{$b['event']->id}/review", ['review_note' => 'hacked']],
            ['get', "/activity-log/{$b['log']->id}"], ['delete', "/activity-log/{$b['log']->id}"],
            ['patch', "/notifications/recipients/{$b['recipient']->id}"], ['delete', "/notifications/recipients/{$b['recipient']->id}"],
            ['patch', "/notifications/phones/{$b['phone']->id}"], ['delete', "/notifications/phones/{$b['phone']->id}"],
            // Forms that take another shop's ids
            ['post', '/stock-adjustments', ['product_id' => $b['product']->id, 'adjustment_type' => 'subtract', 'quantity' => 50, 'reason' => 'x']],
            ['post', '/purchases', ['supplier_id' => $b['supplier']->id, 'purchase_date' => now()->toDateString(), 'status' => 'received',
                'items' => [['product_id' => $b['product']->id, 'quantity' => 1, 'unit_cost' => 1]]]],
            ['postJson', '/pos/cart/add', ['product_id' => $b['product']->id, 'quantity' => 1]],
            ['postJson', '/pos/finalize', ['items' => [['product_id' => $b['product']->id, 'quantity' => 1]], 'invoice_no' => 'X1', 'payment_method' => 'cash', 'payment_status' => 'paid']],
            ['post', '/notifications/z-report/send', ['register' => $b['register']->id]],
            ['get', "/notifications/z-report/preview?register={$b['register']->id}"],
            ['delete', '/notifications/logs'],
        ];

        foreach ($attempts as $attempt) {
            [$method, $url] = $attempt;
            $response = $this->actingAs($this->attacker)->{$method}($url, $attempt[2] ?? []);
            $status = $response->status();
            $content = (string) $response->getContent();

            $this->assertLessThan(500, $status, strtoupper($method) . " $url crashed: " . substr($content, 0, 300));
            $this->assertStringNotContainsString(self::SECRET, $content, strtoupper($method) . " $url leaked the other shop's data");
            $this->assertTrue(!in_array($status, [200, 201], true) || !str_starts_with($method, 'get') || $status === 404,
                "GET $url answered $status for another shop's record");
        }

        $this->assertSame($before, $this->victimCounts(), "Another shop's data was changed");
    }

    public function test_a_session_from_one_shop_cannot_be_used_in_another(): void
    {
        // Logged in to the test shop, then the browser switches to the victim shop's link
        $this->actingAs($this->attacker)->get('/dashboard')->assertOk();
        $this->actingAs($this->attacker)->get('/victim');
        $this->assertGuest();

        // Forcing the victim shop into the session while logged in as the attacker
        $response = $this->actingAs($this->attacker)->withSession(['tenant_id' => $this->victim->id])->get('/products');
        $this->assertContains($response->status(), [302, 403]);
        // Only the shop's public name (as on its login page) may show, none of its records
        foreach ([' Product', ' Customer', ' Supplier', '-INV', 'SECRET-SKU'] as $private) {
            $this->assertStringNotContainsString(self::SECRET . $private, (string) $response->getContent());
        }
        $this->assertGuest();
    }

    public function test_new_records_always_belong_to_the_shop_that_made_them(): void
    {
        $this->actingAs($this->attacker)->post('/categories', ['name' => 'Mine Only']);
        $this->actingAs($this->attacker)->post('/expenses', ['expense_date' => now()->toDateString(), 'category' => 'Rent', 'amount' => 5]);

        $this->assertSame($this->tenant->id, Category::where('name', 'Mine Only')->value('tenant_id'));
        $this->assertSame(1, Expense::count());
        $this->assertSame(1, with_tenant($this->victim, fn () => Expense::count()));
        $this->assertSame(0, with_tenant($this->victim, fn () => Category::where('name', 'Mine Only')->count()));
    }
}
