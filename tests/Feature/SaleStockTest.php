<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\StockHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

/**
 * Stock goes down when a sale is made, follows the change when a sale is edited,
 * and a sale can never take more than is in stock.
 */
class SaleStockTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private function admin(): User
    {
        return $this->userWithPermissions(['add_pos_sell', 'view_pos_sell', 'edit_pos_sell'], 'admin');
    }

    private function sell(User $user, $product, float $qty): Sale
    {
        $this->actingAs($user)->postJson('/pos/finalize', [
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            'invoice_no' => 'INV-' . uniqid(), 'payment_method' => 'cash', 'payment_status' => 'paid',
        ])->assertOk();

        return Sale::latest('id')->firstOrFail();
    }

    private function edit(User $user, Sale $sale, $product, float $qty)
    {
        return $this->actingAs($user)->put("/sales/{$sale->id}", [
            'edit_reason' => 'Customer changed the order',
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'price' => 1000]],
            'payments' => [['method' => 'cash', 'amount' => $qty * 1000]],
        ]);
    }

    public function test_editing_a_sale_up_or_down_moves_stock_by_the_difference(): void
    {
        $admin = $this->admin();
        $this->openRegisterFor($admin);
        $product = $this->product(['current_stock' => 10, 'selling_price' => 1000]);
        $sale = $this->sell($admin, $product, 3);
        $this->assertEquals(7, $product->fresh()->current_stock);

        $this->edit($admin, $sale, $product, 5)->assertSessionHasNoErrors();
        $this->assertEquals(5, $product->fresh()->current_stock);

        $this->edit($admin, $sale->fresh(), $product, 1)->assertSessionHasNoErrors();
        $this->assertEquals(9, $product->fresh()->current_stock);

        $this->assertEquals([-3, -2, 4], StockHistory::orderBy('id')->pluck('quantity_change')->map(fn ($q) => (float) $q)->all());
    }

    public function test_an_edit_cannot_take_more_than_is_in_stock(): void
    {
        $admin = $this->admin();
        $this->openRegisterFor($admin);
        $product = $this->product(['current_stock' => 4, 'selling_price' => 1000]);
        $sale = $this->sell($admin, $product, 3);   // 1 left

        $this->edit($admin, $sale, $product, 5)->assertSessionHasErrors('items');

        $this->assertEquals(1, $product->fresh()->current_stock);
        $this->assertEquals(3, $sale->fresh()->items()->sum('quantity'));
    }

    public function test_sales_form_deducts_stock_and_refuses_more_than_in_stock(): void
    {
        $admin = $this->admin();
        $product = $this->product(['current_stock' => 5, 'selling_price' => 1000]);
        $sale = fn (float $qty) => [
            'invoice_no' => 'INV-' . uniqid(), 'total_amount' => $qty * 1000, 'total_paid' => $qty * 1000, 'total_items' => (int) $qty,
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'price' => 1000]],
        ];

        $this->actingAs($admin)->post('/sales', $sale(6))->assertSessionHasErrors('items');
        $this->assertEquals(5, $product->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 0);

        $this->actingAs($admin)->post('/sales', $sale(2))->assertSessionHasNoErrors();
        $this->assertEquals(3, $product->fresh()->current_stock);
        $this->assertEquals(-2, StockHistory::sole()->quantity_change);
    }
}
