<?php

namespace Tests\Feature;

use App\Models\IntegrityEvent;
use App\Models\Sale;
use App\Models\StockHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class OfflineSaleSyncTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private function sale(array $items, array $extra = [])
    {
        return $this->postJson('/pos/finalize', array_merge([
            'items' => $items,
            'invoice_no' => 'INV-OFFLINE-1',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ], $extra));
    }

    public function test_repeated_request_returns_the_saved_sale_without_selling_twice(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['current_stock' => 10]);
        $items = [['product_id' => $product->id, 'quantity' => 2]];

        $this->actingAs($cashier)->sale($items)->assertOk();
        $this->actingAs($cashier)->sale($items)->assertOk()->assertJson(['success' => true, 'duplicate' => true]);
        $this->actingAs($cashier)->sale($items, ['offline' => true])->assertOk()->assertJson(['duplicate' => true]);

        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals(8, $product->fresh()->current_stock);
        $this->assertSame(1, StockHistory::count());
    }

    public function test_invoice_number_of_another_cashier_is_rejected(): void
    {
        $first = $this->cashier();
        $this->openRegisterFor($first);
        $product = $this->product();
        $this->actingAs($first)->sale([['product_id' => $product->id, 'quantity' => 1]])->assertOk();

        $second = $this->cashier();
        $this->openRegisterFor($second);
        $this->actingAs($second)->sale([['product_id' => $product->id, 'quantity' => 1]])->assertStatus(409);

        $this->assertDatabaseCount('sales', 1);
    }

    public function test_offline_sale_is_recorded_at_the_time_it_was_made(): void
    {
        $cashier = $this->cashier();
        $register = $this->openRegisterFor($cashier);
        $register->update(['opened_at' => now()->subHours(5)]);
        $product = $this->product();
        $soldAt = now()->subHours(2)->startOfMinute();

        $this->actingAs($cashier)
            ->sale([['product_id' => $product->id, 'quantity' => 1]], ['offline' => true, 'sold_at' => $soldAt->toIso8601String()])
            ->assertOk();

        $sale = Sale::sole();
        $this->assertTrue($sale->created_at->equalTo($soldAt));
        $this->assertSame($register->id, $sale->register_id);
        $this->assertSame($soldAt->toDateString(), StockHistory::sole()->date instanceof \DateTimeInterface
            ? StockHistory::sole()->date->format('Y-m-d') : substr(StockHistory::sole()->date, 0, 10));
    }

    public function test_offline_sale_time_in_the_future_is_not_trusted(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product();

        $this->actingAs($cashier)
            ->sale([['product_id' => $product->id, 'quantity' => 1]], ['offline' => true, 'sold_at' => now()->addDays(3)->toIso8601String()])
            ->assertOk();

        $this->assertTrue(Sale::sole()->created_at->lte(now()));
    }

    public function test_offline_sale_beyond_stock_is_kept_and_flagged(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['current_stock' => 1]);

        $this->actingAs($cashier)
            ->sale([['product_id' => $product->id, 'quantity' => 3]], ['offline' => true, 'sold_at' => now()->toIso8601String()])
            ->assertOk();

        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals(0, $product->fresh()->current_stock);
        $this->assertTrue(IntegrityEvent::where('type', 'offline_stock_shortfall')->where('product_id', $product->id)->exists());
    }

    public function test_online_sale_beyond_stock_is_still_rejected(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['current_stock' => 1]);

        $this->actingAs($cashier)->sale([['product_id' => $product->id, 'quantity' => 3]])->assertStatus(400);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_offline_underpaid_split_sale_is_kept_and_flagged(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['selling_price' => 1000]);

        // Price went up from 900 to 1000 while the till was offline
        $this->actingAs($cashier)->sale([['product_id' => $product->id, 'quantity' => 1]], [
            'offline' => true,
            'sold_at' => now()->toIso8601String(),
            'is_split_payment' => true,
            'payments' => [['method' => 'cash', 'amount' => 500], ['method' => 'mpamba', 'amount' => 400]],
            'paid_total' => 900,
        ])->assertOk();

        $this->assertDatabaseCount('sales', 1);
        $this->assertTrue(IntegrityEvent::where('type', 'offline_underpaid')->exists());
    }

    public function test_offline_sale_uploaded_after_register_closed_goes_to_that_register_and_is_flagged(): void
    {
        $cashier = $this->cashier();
        $register = $this->openRegisterFor($cashier);
        $register->update(['opened_at' => now()->subHours(8), 'closed_at' => now()->subHour()]);
        $product = $this->product();

        $this->actingAs($cashier)
            ->sale([['product_id' => $product->id, 'quantity' => 1]], ['offline' => true, 'sold_at' => now()->subHours(3)->toIso8601String()])
            ->assertOk();

        $this->assertSame($register->id, Sale::sole()->register_id);
        $this->assertTrue(IntegrityEvent::where('type', 'offline_late_sync')->exists());
    }
}
