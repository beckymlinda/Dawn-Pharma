<?php

namespace Tests\Feature;

use App\Models\StockAdjustment;
use App\Models\StockHistory;
use App\Models\User;
use App\Services\StockLedger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

/**
 * Product report: opening and closing stock of each day, worked back from today's stock.
 */
class DailyStockTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private function move($product, string $type, float $change, string $at): void
    {
        $product->current_stock += $change;
        $product->save();
        $row = StockHistory::create([
            'product_id' => $product->id, 'type' => $type, 'quantity_change' => $change,
            'new_quantity' => $product->current_stock, 'date' => Carbon::parse($at)->toDateString(),
        ]);
        $row->forceFill(['created_at' => $at])->save();
    }

    private function adjust($product, string $type, float $qty, string $at): void
    {
        $before = $product->current_stock;
        $product->current_stock += $type === 'add' ? $qty : -$qty;
        $product->save();
        $row = StockAdjustment::create([
            'product_id' => $product->id, 'user_id' => $this->cashier()->id, 'adjustment_type' => $type, 'quantity' => $qty,
            'previous_quantity' => $before, 'new_quantity' => $product->current_stock, 'previous_price' => 1000, 'new_price' => 1000, 'reason' => 'Count',
        ]);
        $row->forceFill(['created_at' => $at])->save();
    }

    /** Opening 20 on day 1; each day's closing is the next day's opening. */
    private function history()
    {
        $this->travelTo('2026-10-01 09:00');
        $product = $this->product(['current_stock' => 20]);   // added with 20, no purchase recorded
        $this->travelTo('2026-10-12 15:00');
        $this->move($product, 'Sale', -3, '2026-10-10 09:00');       // day 1: 20 → 17
        $this->move($product, 'Purchase', 10, '2026-10-10 14:00');   //        17 → 27
        $this->adjust($product, 'subtract', 2, '2026-10-11 08:00');  // day 2: 27 → 25
        $this->adjust($product, 'add', 4, '2026-10-11 09:00');       //        25 → 29 (stock in)
        $this->adjust($product, 'subtract', 4, '2026-10-11 10:00');  //        29 → 25
        $this->move($product, 'Sale', -5, '2026-10-12 10:00');       // day 3: 25 → 20 (today)

        return $product;
    }

    public function test_opening_and_closing_stock_per_day(): void
    {
        $product = $this->history();

        $days = app(StockLedger::class)->daily([$product->id], Carbon::parse('2026-10-09'), Carbon::parse('2026-10-12'))[$product->id];

        $this->assertSame(['2026-10-12', '2026-10-11', '2026-10-10', '2026-10-09'], array_keys($days));
        $this->assertEquals(['opening' => 20, 'received' => 0, 'sold' => 0, 'adjusted' => 0, 'closing' => 20], $days['2026-10-09']);
        $this->assertEquals(['opening' => 20, 'received' => 10, 'sold' => 3, 'adjusted' => 0, 'closing' => 27], $days['2026-10-10']);
        $this->assertEquals(['opening' => 27, 'received' => 4, 'sold' => 0, 'adjusted' => -6, 'closing' => 25], $days['2026-10-11']);
        $this->assertEquals(['opening' => 25, 'received' => 0, 'sold' => 5, 'adjusted' => 0, 'closing' => 20], $days['2026-10-12']);
    }

    public function test_product_report_shows_daily_stock(): void
    {
        $product = $this->history();
        $admin = $this->userWithPermissions(['view_stock_report'], 'admin');

        $this->actingAs($admin)->get('/reports/products?date=2026-10-10')
            ->assertOk()
            ->assertSeeInOrder(['Opening', 'Closing', $product->name, '20', '27', '+10 in', '−3 sold']);

        $this->actingAs($admin)->get("/reports/products/{$product->id}?tab=daily&from=2026-10-10&to=2026-10-11")
            ->assertOk()
            ->assertSee('Daily Stock')
            ->assertSeeInOrder(['Sun, Oct 11, 2026', '27', '+4', '−6', '25', 'Sat, Oct 10, 2026', '20', '+10', '−3', '27']);
    }

    public function test_first_stock_counts_as_received_on_the_day_the_product_was_added(): void
    {
        $product = $this->history();

        $days = app(StockLedger::class)->daily([$product->id], Carbon::parse('2026-09-30'), Carbon::parse('2026-10-01'))[$product->id];

        $this->assertEquals(['opening' => 0, 'received' => 0, 'sold' => 0, 'adjusted' => 0, 'closing' => 0], $days['2026-09-30']);
        $this->assertEquals(['opening' => 0, 'received' => 20, 'sold' => 0, 'adjusted' => 0, 'closing' => 20], $days['2026-10-01']);
    }

    public function test_stock_in_adjustments_and_first_stock_count_as_purchases(): void
    {
        $product = $this->history();   // 20 first stock + 10 purchased + 4 stock-in adjustment
        $admin = $this->userWithPermissions(['view_stock_report'], 'admin');

        $this->assertEquals([$product->id => 24], app(StockLedger::class)->stockInTotals([$product->id]));

        $this->actingAs($admin)->get('/reports/products')
            ->assertOk()
            ->assertSeeInOrder([$product->name, '24']);   // total purchased

        // One purchases list, newest first, with no separate "stock in" section
        $this->actingAs($admin)->get("/reports/products/{$product->id}?tab=purchases")
            ->assertOk()
            ->assertDontSee('No purchases found')
            ->assertDontSee('stock in without a purchase is listed below')
            ->assertSeeInOrder(['2 purchase(s)', 'Sunday, Oct 11, 2026', 'Stock in', '4', 'Thursday, Oct 01, 2026', 'First stock', '20']);
    }
}
