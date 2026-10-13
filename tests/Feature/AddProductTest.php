<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class AddProductTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    public function test_product_with_opening_stock_can_be_added_without_a_supplier(): void
    {
        $manager = $this->userWithPermissions(['add_product', 'view_product'], 'manager');

        $this->actingAs($manager)->post('/products', [
            'name' => 'Paracetamol 500mg',
            'purchase_price' => 100,
            'selling_price' => 150,
            'purchase_quantity' => 20,
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        $product = Product::where('name', 'Paracetamol 500mg')->firstOrFail();
        $this->assertEquals(20, $product->current_stock);

        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertNull($purchase->supplier_id);
        $this->assertEquals(2000, $purchase->grand_total);

        $this->actingAs($this->userWithPermissions(['view_all_purchase'], 'viewer'))
            ->get("/purchases/{$purchase->id}")->assertOk();
    }
}
