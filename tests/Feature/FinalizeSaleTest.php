<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class FinalizeSaleTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private function finalize(array $items, array $extra = [])
    {
        return $this->postJson('/pos/finalize', array_merge([
            'items' => $items,
            'invoice_no' => 'INV-' . uniqid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ], $extra));
    }

    public function test_sale_records_items_and_deducts_stock(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['current_stock' => 10, 'selling_price' => 1000]);

        $this->actingAs($cashier)
            ->finalize([['product_id' => $product->id, 'quantity' => 3]])
            ->assertOk()
            ->assertJson(['success' => true]);

        $sale = Sale::sole();
        $this->assertEquals(3000, $sale->total_amount);
        $this->assertEquals($cashier->id, $sale->user_id);
        $this->assertEquals(7, $product->fresh()->current_stock);
        $this->assertEquals(-3, StockHistory::sole()->quantity_change);
        $this->assertEquals(600, SaleItem::sole()->purchase_cost);
    }

    public function test_price_sent_by_the_browser_is_ignored(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['selling_price' => 1000]);

        $this->actingAs($cashier)
            ->finalize([['product_id' => $product->id, 'quantity' => 2, 'price' => 1, 'subtotal' => 2]])
            ->assertOk();

        $this->assertEquals(1000, SaleItem::sole()->price);
        $this->assertEquals(2000, SaleItem::sole()->subtotal);
        $this->assertEquals(2000, Sale::sole()->total_amount);
    }

    public function test_cannot_sell_more_than_in_stock(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['current_stock' => 2]);

        $this->actingAs($cashier)
            ->finalize([['product_id' => $product->id, 'quantity' => 3]])
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(2, $product->fresh()->current_stock);
    }

    public function test_stock_check_counts_the_same_product_on_several_lines(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['current_stock' => 5]);

        // Each line alone fits in stock, together they do not
        $this->actingAs($cashier)
            ->finalize([
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3],
            ])
            ->assertStatus(400);

        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(5, $product->fresh()->current_stock);
    }

    public function test_zero_or_negative_quantity_is_rejected(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['current_stock' => 5]);

        $this->actingAs($cashier)
            ->finalize([['product_id' => $product->id, 'quantity' => -4]])
            ->assertStatus(400);

        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(5, $product->fresh()->current_stock);
    }

    public function test_split_payment_must_cover_the_total(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['selling_price' => 1000, 'current_stock' => 5]);

        $this->actingAs($cashier)
            ->finalize([['product_id' => $product->id, 'quantity' => 2]], [
                'is_split_payment' => true,
                'payments' => [
                    ['method' => 'cash', 'amount' => 500],
                    ['method' => 'airtel_money', 'amount' => 500],
                ],
            ])
            ->assertStatus(400);

        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(5, $product->fresh()->current_stock);
    }

    public function test_split_payment_that_covers_the_total_succeeds(): void
    {
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['selling_price' => 1000, 'current_stock' => 5]);

        $this->actingAs($cashier)
            ->finalize([['product_id' => $product->id, 'quantity' => 2]], [
                'is_split_payment' => true,
                'payments' => [
                    ['method' => 'cash', 'amount' => 1500],
                    ['method' => 'airtel_money', 'amount' => 500],
                ],
            ])
            ->assertOk();

        $this->assertEquals('split', Sale::sole()->payment_method);
        $this->assertDatabaseCount('payments', 2);
        $this->assertEquals(3, $product->fresh()->current_stock);
    }

    public function test_cannot_sell_without_an_open_register(): void
    {
        $cashier = $this->cashier();
        $product = $this->product();

        $this->actingAs($cashier)
            ->finalize([['product_id' => $product->id, 'quantity' => 1]])
            ->assertStatus(400);

        $this->assertDatabaseCount('sales', 0);
    }
}
