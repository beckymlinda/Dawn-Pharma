<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class ProfitLossTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function owner()
    {
        return $this->userWithPermissions(['view_profit_loss_report', 'access_all_expenses', 'add_expense', 'delete_expense'], 'admin');
    }

    /** Record a sale the way the till does, at a given time */
    private function sale($product, float $qty, string $at, ?array $payments = null): Sale
    {
        $total = $product->selling_price * $qty;
        $register = $this->openRegisterFor($this->cashier());
        $sale = Sale::create(['register_id' => $register->id, 'invoice_no' => 'T-' . uniqid(), 'payment_status' => 'paid',
            'payment_method' => $payments ? 'split' : 'cash', 'total_amount' => $total, 'total_paid' => $total, 'total_items' => $qty]);
        $sale->forceFill(['created_at' => $at])->saveQuietly();
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => $qty, 'price' => $product->selling_price,
            'subtotal' => $total, 'purchase_cost' => $product->purchase_price]);
        foreach ($payments ?? [['cash', $total]] as [$method, $amount]) {
            Payment::create(['sale_id' => $sale->id, 'method' => $method, 'amount' => $amount]);
        }
        $product->decrement('current_stock', $qty);
        StockHistory::create(['product_id' => $product->id, 'type' => 'Sale', 'quantity_change' => -$qty,
            'new_quantity' => $product->fresh()->current_stock, 'date' => substr($at, 0, 10)])->forceFill(['created_at' => $at])->saveQuietly();

        return $sale;
    }

    private function report(array $query = ['date_range' => 'this_month'])
    {
        return $this->actingAs($this->owner())->get('/reports/profit-loss?' . http_build_query($query))->assertOk();
    }

    public function test_net_profit_takes_off_written_off_stock_and_expenses(): void
    {
        $product = $this->product(['purchase_price' => 600, 'selling_price' => 1000, 'current_stock' => 50]);
        $this->sale($product, 10, '2026-10-05 10:00:00');                       // sales 10,000, cost 6,000

        StockAdjustment::create(['product_id' => $product->id, 'user_id' => $this->owner()->id, 'adjustment_type' => 'subtract',
            'quantity' => 2, 'previous_quantity' => 40, 'new_quantity' => 38, 'previous_price' => 1000, 'new_price' => 1000,
            'reason' => 'Expired'])->forceFill(['created_at' => '2026-10-06 09:00:00'])->saveQuietly();   // 1,200 at cost
        $product->decrement('current_stock', 2);

        Expense::create(['expense_date' => '2026-10-01', 'category' => 'Rent', 'amount' => 1500]);
        Expense::create(['expense_date' => '2026-09-30', 'category' => 'Rent', 'amount' => 9999]);  // other month

        $response = $this->report();

        $response->assertViewHas('totalSales', 10000.0)
            ->assertViewHas('costOfGoodsSold', 6000.0)
            ->assertViewHas('grossProfit', 4000.0)
            ->assertViewHas('stockWrittenOff', 1200.0)
            ->assertViewHas('operatingExpenses', 1500.0)
            ->assertViewHas('netProfit', 1300.0)
            ->assertViewHas('writeOffsByReason', ['Expired' => 1200.0])
            ->assertViewHas('expensesByCategory', ['Rent' => 1500.0]);
    }

    public function test_split_payments_count_per_method_and_change_is_not_revenue(): void
    {
        $product = $this->product(['selling_price' => 1000, 'current_stock' => 50]);
        // 3,000 sale: 1,000 Airtel Money + 2,500 cash handed over (500 change)
        $this->sale($product, 3, '2026-10-05 10:00:00', [['airtel_money', 1000], ['cash', 2500]]);

        $breakdown = collect($this->report()->viewData('paymentBreakdown'))->keyBy('method');

        $this->assertEquals(2000, $breakdown['Cash']['total']);
        $this->assertEquals(1000, $breakdown['Airtel Money']['total']);
    }

    public function test_stock_at_start_and_end_of_a_past_month_is_rebuilt(): void
    {
        $product = $this->product(['purchase_price' => 100, 'current_stock' => 30]);
        // September: sold 5 on the 10th. October: sold 4 on the 5th. Stock now 21.
        $this->sale($product, 5, '2026-09-10 10:00:00');
        $this->sale($product, 4, '2026-10-05 10:00:00');

        $september = $this->report(['date_range' => 'last_month']);

        $september->assertViewHas('openingStockCost', 3000.0)   // 30 units on 1 Sep
            ->assertViewHas('closingStockCost', 2500.0);          // 25 units on 30 Sep

        $this->report()->assertViewHas('openingStockCost', 2500.0)  // October starts where September ended
            ->assertViewHas('closingStockCost', 2100.0);
    }

    public function test_stock_movement_adds_up_with_deliveries(): void
    {
        $product = $this->product(['purchase_price' => 100, 'current_stock' => 30]);
        $this->sale($product, 4, '2026-10-05 10:00:00');

        // Delivery of 10 on 8 Oct
        $product->increment('current_stock', 10);
        StockHistory::create(['product_id' => $product->id, 'type' => 'Purchase', 'quantity_change' => 10,
            'new_quantity' => $product->fresh()->current_stock, 'unit_cost' => 100, 'date' => '2026-10-08'])
            ->forceFill(['created_at' => '2026-10-08 09:00:00'])->saveQuietly();

        $this->report()
            ->assertViewHas('openingStockCost', 3000.0)    // 30 on 1 Oct
            ->assertViewHas('stockReceived', 1000.0)       // + 10
            ->assertViewHas('costOfGoodsSold', 400.0)      // - 4
            ->assertViewHas('closingStockCost', 3600.0)    // 36 now
            ->assertViewHas('otherStockChanges', 0);
    }

    public function test_this_week_shows_this_week(): void
    {
        $this->report(['date_range' => 'this_week'])
            ->assertViewHas('fromDateLabel', '12 Oct 2026')
            ->assertViewHas('toDateLabel', '18 Oct 2026');
    }

    public function test_month_in_progress_is_compared_with_the_same_days_of_last_month(): void
    {
        $product = $this->product(['selling_price' => 1000, 'current_stock' => 100]);
        $this->sale($product, 2, '2026-09-10 10:00:00');   // within 1-15 Sep
        $this->sale($product, 9, '2026-09-25 10:00:00');   // later in September: not compared
        $this->sale($product, 3, '2026-10-10 10:00:00');

        $response = $this->report();

        $this->assertEquals(50.0, $response->viewData('deltas')['revenue']);   // 3,000 vs 2,000
        $this->assertStringContainsString('01 Sep', $response->viewData('comparisonLabel'));
    }

    public function test_page_explains_when_no_expenses_are_recorded(): void
    {
        $this->report()->assertSee('No expenses recorded for this period');
    }

    public function test_expenses_can_be_recorded_and_deleted_by_permitted_users(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post('/expenses', ['expense_date' => '2026-10-02', 'category' => 'Electricity & Water', 'amount' => 8500])
            ->assertRedirect();
        $expense = Expense::sole();
        $this->assertSame($owner->id, $expense->user_id);

        $this->actingAs($owner)->get('/expenses')->assertOk()->assertSee('Electricity &amp; Water', false);

        $this->actingAs($owner)->delete('/expenses/' . $expense->id)->assertRedirect();
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_cashier_cannot_see_or_add_expenses(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->get('/expenses')->assertForbidden();
        $this->actingAs($cashier)->post('/expenses', ['expense_date' => '2026-10-02', 'category' => 'Rent', 'amount' => 1])->assertForbidden();
    }

    public function test_expense_needs_a_known_category_and_no_future_date(): void
    {
        $this->actingAs($this->owner())
            ->post('/expenses', ['expense_date' => '2026-12-01', 'category' => 'Holiday', 'amount' => 100])
            ->assertSessionHasErrors(['expense_date', 'category']);
    }
}
