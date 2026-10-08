<?php

namespace Tests\Feature;

use App\Models\IntegrityEvent;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

/**
 * Every Loss Prevention alert is raised by the real action that causes it,
 * and the Loss Prevention page shows, filters, ranks and reviews them.
 */
class LossPreventionTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private function admin(): User
    {
        return $this->userWithPermissions(['add_pos_sell', 'view_pos_sell', 'edit_pos_sell', 'delete_pos_sell', 'close_cash_register', 'create_stock_adjustment'], 'admin');
    }

    private function sell(User $user, $product, float $qty = 1): Sale
    {
        $this->actingAs($user)->postJson('/pos/finalize', [
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            'invoice_no' => 'INV-' . uniqid(), 'payment_method' => 'cash', 'payment_status' => 'paid',
        ])->assertOk();

        return Sale::latest('id')->firstOrFail();
    }

    private function inBusinessHours(): void
    {
        Setting::updateOrCreate(['key' => 'lp_open_time'], ['value' => '00:00']);
        Setting::updateOrCreate(['key' => 'lp_close_time'], ['value' => '23:59']);
    }

    private function events(string $type)
    {
        return IntegrityEvent::where('type', $type)->get();
    }

    public function test_normal_sale_in_business_hours_raises_nothing(): void
    {
        $this->inBusinessHours();
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);

        $this->sell($cashier, $this->product());

        $this->assertSame(0, IntegrityEvent::count());
    }

    public function test_sale_below_cost_and_after_hours(): void
    {
        Setting::updateOrCreate(['key' => 'lp_open_time'], ['value' => '00:00']);
        Setting::updateOrCreate(['key' => 'lp_close_time'], ['value' => '00:00']);   // the shop is never open
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);

        $sale = $this->sell($cashier, $this->product(['purchase_price' => 1500, 'selling_price' => 1000]), 2);

        $below = $this->events('below_cost')->first();
        $this->assertNotNull($below);
        $this->assertSame('high', $below->severity);
        $this->assertEquals(1000, $below->amount);                // (1500 - 1000) x 2
        $this->assertSame($cashier->id, $below->user_id);
        $this->assertSame($sale->id, $below->sale_id);
        $this->assertCount(1, $this->events('after_hours_sale'));
    }

    public function test_a_supervisor_lowering_a_price_is_flagged_with_the_loss(): void
    {
        $this->inBusinessHours();
        $admin = $this->admin();
        $this->openRegisterFor($admin);
        $product = $this->product(['selling_price' => 1000, 'purchase_price' => 600]);
        $sale = $this->sell($admin, $product, 2);

        $this->actingAs($admin)->put("/sales/{$sale->id}", [
            'edit_reason' => 'Customer discount',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 700]],
            'payments' => [['method' => 'cash', 'amount' => 1400]],
        ])->assertSessionHasNoErrors();

        $override = $this->events('price_override')->first();
        $this->assertNotNull($override);
        $this->assertSame('critical', $override->severity);      // 30% below the set price
        $this->assertEquals(600, $override->amount);
        $edit = $this->events('sale_edited')->first();
        $this->assertSame('high', $edit->severity);              // total went down
        $this->assertStringContainsString('Customer discount', $edit->description);
        $this->assertEquals(2000, $edit->details['before']['total_amount']);
        $this->assertEquals(1400, $edit->details['after']['total_amount']);
    }

    public function test_deleting_a_sale_keeps_a_full_copy(): void
    {
        $this->inBusinessHours();
        $admin = $this->admin();
        $this->openRegisterFor($admin);
        $product = $this->product(['name' => 'Amoxil']);
        $sale = $this->sell($admin, $product, 3);

        $this->actingAs($admin)->delete("/sales/{$sale->id}");

        $this->assertNull(Sale::find($sale->id));
        $deleted = $this->events('sale_deleted')->first();
        $this->assertSame('critical', $deleted->severity);
        $this->assertEquals(3000, $deleted->amount);
        $this->assertSame('Amoxil', $deleted->details['items'][0]['name']);
        $this->assertEquals(3, $deleted->details['items'][0]['quantity']);
        $this->assertEquals(10, $product->fresh()->current_stock);   // stock returned
    }

    public function test_refused_delete_and_edit_attempts_are_logged(): void
    {
        $this->inBusinessHours();
        $owner = $this->cashier();
        $register = $this->openRegisterFor($owner);
        $product = $this->product();
        $sale = $this->sell($owner, $product);

        // A cashier without delete permission
        $this->actingAs($owner)->delete("/sales/{$sale->id}");
        $this->assertNotNull(Sale::find($sale->id));
        $this->assertCount(1, $this->events('delete_blocked'));

        // A cashier without edit permission
        $this->actingAs($owner)->put("/sales/{$sale->id}", [
            'edit_reason' => 'Changing it',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['method' => 'cash', 'amount' => 1000]],
        ])->assertSessionHasErrors('sale');

        // Another cashier who may edit, but not someone else's register
        $other = $this->userWithPermissions(['add_pos_sell', 'view_pos_sell', 'edit_pos_sell'], 'cashier');
        $this->actingAs($other)->put("/sales/{$sale->id}", [
            'edit_reason' => 'Changing it',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['method' => 'card', 'amount' => 1000]],
        ])->assertSessionHasErrors('sale');

        $this->assertCount(2, $this->events('edit_blocked'));
        $this->assertSame('cash', $sale->fresh()->payment_method);
    }

    public function test_cash_count_shortage_surplus_and_tolerance(): void
    {
        $this->inBusinessHours();
        Setting::updateOrCreate(['key' => 'lp_cash_tolerance'], ['value' => '500']);
        // Within 500 is fine; short by 10x the tolerance (5,000) is critical
        $cases = [[0, 'cash_shortage', 'critical'], [4000, 'cash_shortage', 'high'], [5300, null, null], [4600, null, null], [6000, 'cash_surplus', 'medium']];

        foreach ($cases as [$counted, $type, $severity]) {
            IntegrityEvent::query()->delete();
            $cashier = $this->userWithPermissions(['add_pos_sell', 'view_pos_sell', 'close_cash_register'], 'cashier');
            $register = Register::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 5000]);
            // expected cash = 5,000 float (no sales)

            $this->actingAs($cashier)->patch("/pos/register/{$register->id}", ['counted_cash' => $counted]);

            $register->refresh();
            $this->assertNotNull($register->closed_at);
            $this->assertEquals($counted - 5000, $register->cash_variance);
            $event = IntegrityEvent::whereIn('type', ['cash_shortage', 'cash_surplus'])->first();
            $this->assertSame($type, $event?->type, "counted $counted");
            $this->assertSame($severity, $event?->severity, "counted $counted");
        }
    }

    public function test_cart_voids_and_stock_removal(): void
    {
        $this->inBusinessHours();
        $admin = $this->admin();
        $this->openRegisterFor($admin);
        $product = $this->product(['purchase_price' => 600]);

        $this->actingAs($admin)->postJson('/pos/integrity/void', ['type' => 'line_void', 'reason' => 'removed', 'removed_quantity' => 2,
            'items' => [['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2, 'price' => 1000]]])->assertOk();
        $this->actingAs($admin)->postJson('/pos/integrity/void', ['type' => 'cart_cleared', 'reason' => 'cleared by user',
            'items' => [['product_id' => $product->id, 'name' => $product->name, 'quantity' => 3, 'price' => 1000]]])->assertOk();
        $this->actingAs($admin)->post('/stock-adjustments', ['product_id' => $product->id, 'adjustment_type' => 'subtract', 'quantity' => 4, 'reason' => 'Expired']);

        $this->assertEquals(2000, $this->events('line_void')->first()->amount);
        $this->assertEquals(3000, $this->events('cart_cleared')->first()->amount);
        $this->assertCount(1, $this->events('stock_removed'));
        $this->assertEquals(6, $product->fresh()->current_stock);
    }

    public function test_wrong_passwords_are_logged_and_escalate(): void
    {
        $cashier = User::factory()->create(['email' => 'mary@shop.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'mary@shop.test', 'password' => 'wrong-' . $i]);
        }

        $logins = $this->events('failed_login');
        $this->assertCount(5, $logins);
        $this->assertSame($cashier->id, $logins->first()->user_id);
        $this->assertSame('high', $logins->last()->severity);
        $this->assertSame($this->tenant->id, $logins->first()->tenant_id);
    }

    public function test_dashboard_ranks_filters_and_reviews(): void
    {
        $admin = $this->admin();
        $risky = User::factory()->create(['name' => 'Risky Rita']);
        $careful = User::factory()->create(['name' => 'Careful Cate']);
        foreach (range(1, 3) as $i) {
            IntegrityEvent::create(['type' => 'sale_deleted', 'severity' => 'critical', 'user_id' => $risky->id, 'amount' => 5000, 'description' => "Deleted sale $i"]);
        }
        IntegrityEvent::create(['type' => 'line_void', 'severity' => 'low', 'user_id' => $careful->id, 'amount' => 100, 'description' => 'Removed one item']);
        IntegrityEvent::create(['type' => 'cash_shortage', 'severity' => 'high', 'user_id' => $careful->id, 'amount' => 800, 'description' => 'Short by 800']);
        $old = IntegrityEvent::create(['type' => 'sale_deleted', 'severity' => 'critical', 'user_id' => $careful->id, 'amount' => 99999, 'description' => 'Old deletion']);
        $old->forceFill(['created_at' => now()->subDays(30)])->save();

        $page = $this->actingAs($admin)->get('/loss-prevention')->assertOk();
        $page->assertSee('Risky Rita')->assertSee('Careful Cate')->assertDontSee('Old deletion');
        $this->assertLessThan(strpos($page->getContent(), 'Careful Cate'), strpos($page->getContent(), 'Risky Rita'));   // ranked first
        $this->assertSame(4, $page->viewData('kpis')['needs_review']);
        $this->assertEquals(15000, $page->viewData('kpis')['deleted_value']);
        $this->assertEquals(800, $page->viewData('kpis')['shortage_value']);

        // Filters
        $this->actingAs($admin)->get('/loss-prevention?type=line_void')->assertSee('Removed one item')->assertDontSee('Deleted sale 1');
        $this->actingAs($admin)->get('/loss-prevention?user=' . $careful->id)->assertSee('Short by 800')->assertDontSee('Deleted sale 1');
        $this->actingAs($admin)->get('/loss-prevention?from=' . now()->subDays(40)->toDateString())->assertSee('Old deletion');

        // Review with a note, then reopen
        $event = IntegrityEvent::where('description', 'Short by 800')->first();
        $this->actingAs($admin)->get("/loss-prevention/{$event->id}")->assertOk()->assertSee('Short by 800');
        $this->actingAs($admin)->patch("/loss-prevention/{$event->id}/review", ['review_note' => 'Counted again, found in safe']);
        $event->refresh();
        $this->assertTrue($event->reviewed);
        $this->assertSame($admin->id, $event->reviewed_by);
        $this->assertSame('Counted again, found in safe', $event->review_note);
        $this->actingAs($admin)->get('/loss-prevention?status=open')->assertDontSee('Short by 800');
        $this->assertSame(3, $this->actingAs($admin)->get('/loss-prevention')->viewData('kpis')['needs_review']);
        $this->actingAs($admin)->patch("/loss-prevention/{$event->id}/review");
        $this->assertFalse($event->fresh()->reviewed);
    }

    public function test_settings_are_validated_and_used(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/loss-prevention/settings', ['lp_open_time' => '25:00', 'lp_close_time' => '18:00', 'lp_cash_tolerance' => '-5'])
            ->assertSessionHasErrors(['lp_open_time', 'lp_cash_tolerance']);
        $this->actingAs($admin)->post('/loss-prevention/settings', ['lp_open_time' => '08:00', 'lp_close_time' => '18:00', 'lp_cash_tolerance' => '1000'])
            ->assertSessionHasNoErrors();

        $this->assertSame('1000', IntegrityEvent::setting('lp_cash_tolerance'));
        $this->assertFalse(IntegrityEvent::cashCountRequired());   // checkbox left off
        $this->assertTrue(\App\Services\IntegrityMonitor::isAfterHours(now()->setTime(7, 30)));
        $this->assertFalse(\App\Services\IntegrityMonitor::isAfterHours(now()->setTime(12, 0)));
        $this->assertTrue(\App\Services\IntegrityMonitor::isAfterHours(now()->setTime(18, 0)));
    }

    public function test_cashiers_cannot_see_review_or_change_loss_prevention(): void
    {
        $cashier = $this->cashier();
        $event = IntegrityEvent::create(['type' => 'sale_deleted', 'severity' => 'critical', 'user_id' => $cashier->id, 'amount' => 1, 'description' => 'x']);

        $this->actingAs($cashier)->get('/loss-prevention')->assertForbidden();
        $this->actingAs($cashier)->get("/loss-prevention/{$event->id}")->assertForbidden();
        $this->actingAs($cashier)->patch("/loss-prevention/{$event->id}/review")->assertForbidden();
        $this->actingAs($cashier)->post('/loss-prevention/settings', ['lp_open_time' => '00:00', 'lp_close_time' => '23:59', 'lp_cash_tolerance' => 999999])->assertForbidden();
        $this->assertFalse($event->fresh()->reviewed);
        $this->assertNull(Setting::where('key', 'lp_cash_tolerance')->first());
    }
}
