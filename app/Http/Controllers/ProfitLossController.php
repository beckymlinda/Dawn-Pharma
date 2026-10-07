<?php

namespace App\Http\Controllers;

use App\Helpers\PaymentMethods;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Profit & Loss:
 *   Sales revenue − cost of goods sold         = gross profit
 *   gross profit − stock written off − expenses = net profit
 *
 * Stock values use each product's current purchase price. Stock levels at the
 * start and end of a past period are rebuilt from the stock history and the
 * stock adjustments made after that moment.
 */
class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $dateRange = $request->input('date_range', 'this_month');

        if ($dateRange === 'custom' && $request->filled('from_date') && $request->filled('to_date')) {
            try {
                $fromDate = Carbon::createFromFormat('Y-m-d', $request->input('from_date'))->startOfDay();
                $toDate = Carbon::createFromFormat('Y-m-d', $request->input('to_date'))->endOfDay();
            } catch (\Throwable $e) {
                [$fromDate, $toDate] = $this->getDateRange('this_month');
            }
            if ($fromDate->gt($toDate)) {
                [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
            }
            $days = $fromDate->copy()->startOfDay()->diffInDays($toDate->copy()->startOfDay()) + 1;
            $priorFromDate = $fromDate->copy()->subDays($days);
            $priorToDate = $fromDate->copy()->subSecond();
        } else {
            [$fromDate, $toDate] = $this->getDateRange($dateRange);
            [$priorFromDate, $priorToDate] = $this->getPriorDateRange($dateRange, $fromDate, $toDate);
        }

        // A period still in progress (e.g. this month) is compared with the same
        // number of days of the previous period, not with all of it
        $comparingPartPeriod = $toDate->isFuture() && $fromDate->isPast();
        if ($comparingPartPeriod) {
            $priorToDate = $priorFromDate->copy()->add($fromDate->diff(now()))->min($priorToDate);
        }

        $current = $this->calculateMetrics($fromDate, $toDate);
        $prior = $this->calculateMetrics($priorFromDate, $priorToDate);

        // % change against the previous period of the same length (null = nothing to compare with)
        $deltas = [
            'revenue' => $this->calculateDelta($current['totalSales'], $prior['totalSales']),
            'gross_profit' => $this->calculateDelta($current['grossProfit'], $prior['grossProfit']),
            'net_profit' => $this->calculateDelta($current['netProfit'], $prior['netProfit']),
            'transactions' => $this->calculateDelta($current['totalTransactions'], $prior['totalTransactions']),
        ];

        // Stock at the start and the end of the period
        $products = Product::all();
        $openingQty = $this->stockQuantitiesAt($fromDate, $products);
        $closingQty = $this->stockQuantitiesAt($toDate, $products);
        $value = fn (array $qty, string $priceField) => $products->sum(fn ($p) => ($qty[$p->id] ?? 0) * (float) $p->{$priceField});

        $openingStockCost = $value($openingQty, 'purchase_price');
        $openingStockRetail = $value($openingQty, 'selling_price');
        $closingStockCost = $value($closingQty, 'purchase_price');
        $closingStockRetail = $value($closingQty, 'selling_price');
        $totalStockUnits = array_sum($closingQty);

        $totalPurchase = (float) Purchase::whereBetween('purchase_date', [$fromDate->toDateString(), $toDate->toDateString()])->sum('grand_total');

        // Stock received in the period (purchases and opening stock of new products), valued like the stock itself
        $stockReceived = StockHistory::with('product')
            ->whereIn('type', ['Purchase', 'Opening Stock'])
            ->where('quantity_change', '>', 0)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get()
            ->sum(fn ($h) => (float) $h->quantity_change * (float) ($h->product->purchase_price ?? $h->unit_cost ?? 0));

        // Whatever else moved the stock value (price updates, stock found, deleted sales), so the movement adds up
        $otherStockChanges = $closingStockCost - ($openingStockCost + $stockReceived - $current['costOfGoodsSold'] - $current['stockWrittenOff']);

        $averageStockCost = ($openingStockCost + $closingStockCost) / 2;
        $inventoryTurnover = $averageStockCost > 0 ? $current['costOfGoodsSold'] / $averageStockCost : 0;

        // Products and categories that made the most profit in the period
        $saleItems = SaleItem::with('product.category')
            ->whereHas('sale', fn ($q) => $q->whereBetween('created_at', [$fromDate, $toDate]))
            ->get();

        $topProducts = $saleItems->groupBy('product_id')->map(function ($items) {
            $product = $items->first()->product;
            $revenue = $items->sum(fn ($i) => $this->lineRevenue($i));
            $cost = $items->sum(fn ($i) => $this->lineCost($i));

            return [
                'name' => $product->name ?? 'Deleted product',
                'qty' => $items->sum('quantity'),
                'revenue' => $revenue,
                'profit' => $revenue - $cost,
                'margin' => $revenue > 0 ? round(($revenue - $cost) / $revenue * 100, 1) : 0,
            ];
        })->sortByDesc('profit')->take(10)->values();

        $categoryMargins = $saleItems->groupBy(fn ($i) => $i->product->category->name ?? 'Uncategorized')
            ->map(function ($items) {
                $revenue = $items->sum(fn ($i) => $this->lineRevenue($i));
                $cost = $items->sum(fn ($i) => $this->lineCost($i));

                return [
                    'revenue' => $revenue,
                    'profit' => $revenue - $cost,
                    'margin' => $revenue > 0 ? round(($revenue - $cost) / $revenue * 100, 1) : 0,
                ];
            })->sortByDesc('profit')->take(5)->toArray();

        // In stock, but not sold in the last 30 days: cash tied up on the shelf
        $recentlySold = SaleItem::whereHas('sale', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->distinct()->pluck('product_id');
        $slowMovingProducts = Product::where('current_stock', '>', 0)
            ->whereNotIn('id', $recentlySold)
            ->orderByRaw('current_stock * purchase_price DESC')
            ->take(10)
            ->get();
        $slowMovingValue = Product::where('current_stock', '>', 0)->whereNotIn('id', $recentlySold)
            ->sum(DB::raw('current_stock * purchase_price'));

        // Last six months, month by month
        $seasonalityTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->startOfMonth()->subMonths($i);
            $month = $this->calculateMetrics($monthStart, $monthStart->copy()->endOfMonth());
            $seasonalityTrend[] = [
                'month' => $monthStart->format('M Y'),
                'revenue' => $month['totalSales'],
                'expenses' => $month['costOfGoodsSold'] + $month['stockWrittenOff'] + $month['operatingExpenses'],
                'net_profit' => $month['netProfit'],
            ];
        }

        return view('pos.profit-loss', array_merge([
            'currency' => 'MWK',
            'dateRange' => $dateRange,
            'toDate' => $toDate,
            'fromDateDisplay' => $fromDate->toDateString(),
            'toDateDisplay' => $toDate->toDateString(),
            'fromDateLabel' => $fromDate->format('d M Y'),
            'toDateLabel' => $toDate->format('d M Y'),
            'deltas' => $deltas,
            'comparisonLabel' => $comparingPartPeriod
                ? 'vs ' . $priorFromDate->format('d M') . '–' . $priorToDate->format('d M')
                : 'vs previous period',
            'openingStockCost' => $openingStockCost,
            'openingStockRetail' => $openingStockRetail,
            'closingStockCost' => $closingStockCost,
            'closingStockRetail' => $closingStockRetail,
            'totalStockUnits' => $totalStockUnits,
            'totalPurchase' => $totalPurchase,
            'stockReceived' => $stockReceived,
            'otherStockChanges' => abs($otherStockChanges) < 1 ? 0 : $otherStockChanges,
            'inventoryTurnover' => $inventoryTurnover,
            'topProducts' => $topProducts,
            'categoryMargins' => $categoryMargins,
            'slowMovingProducts' => $slowMovingProducts,
            'slowMovingValue' => (float) $slowMovingValue,
            'seasonalityTrend' => $seasonalityTrend,
        ], $current));
    }

    private function calculateMetrics(Carbon $start, Carbon $end): array
    {
        $sales = Sale::with('payments')->whereBetween('created_at', [$start, $end])->get();
        $saleItems = SaleItem::with('product')->whereIn('sale_id', $sales->pluck('id'))->get();

        $totalSales = (float) $sales->sum('total_amount');
        $totalTransactions = $sales->count();
        $costOfGoodsSold = $saleItems->sum(fn ($item) => $this->lineCost($item));
        $grossProfit = $totalSales - $costOfGoodsSold;

        // Stock removed through stock adjustments (expired, damaged, missing), valued at cost
        $writeOffs = StockAdjustment::with('product')
            ->where('adjustment_type', 'subtract')
            ->whereBetween('created_at', [$start, $end])
            ->get();
        $stockWrittenOff = $writeOffs->sum(fn ($a) => (float) $a->quantity * (float) ($a->product->purchase_price ?? 0));
        $writeOffsByReason = $writeOffs->groupBy(fn ($a) => ucfirst(trim(preg_replace('/\s*\(.*\)$/', '', $a->reason))) ?: 'Other')
            ->map(fn ($group) => $group->sum(fn ($a) => (float) $a->quantity * (float) ($a->product->purchase_price ?? 0)))
            ->sortDesc()->toArray();

        // Running costs recorded under Expenses
        $expensesByCategory = Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('category, SUM(amount) as total')->groupBy('category')->orderByDesc('total')
            ->pluck('total', 'category')->map(fn ($v) => (float) $v)->toArray();
        $operatingExpenses = array_sum($expensesByCategory);

        $netProfit = $grossProfit - $stockWrittenOff - $operatingExpenses;

        return [
            'totalSales' => $totalSales,
            'totalTransactions' => $totalTransactions,
            'totalItemsSold' => (float) $saleItems->sum('quantity'),
            'costOfGoodsSold' => $costOfGoodsSold,
            'grossProfit' => $grossProfit,
            'grossMargin' => $totalSales > 0 ? round($grossProfit / $totalSales * 100, 1) : 0,
            'stockWrittenOff' => $stockWrittenOff,
            'writeOffsByReason' => $writeOffsByReason,
            'operatingExpenses' => $operatingExpenses,
            'expensesByCategory' => $expensesByCategory,
            'netProfit' => $netProfit,
            'netMargin' => $totalSales > 0 ? round($netProfit / $totalSales * 100, 1) : 0,
            'paymentBreakdown' => $this->paymentBreakdown($sales),
        ];
    }

    /**
     * Money received per payment method. Split sales are counted per payment
     * (so Airtel Money / Mpamba show their real share), and cash tendered above
     * the sale total is change given back, not revenue.
     */
    private function paymentBreakdown($sales): array
    {
        $names = PaymentMethods::getDisplayNames();
        $totals = [];

        foreach ($sales as $sale) {
            $payments = $sale->payments->map(fn ($p) => ['method' => $p->method, 'amount' => (float) $p->amount])->all();
            if (!$payments) {
                $payments = [['method' => $sale->payment_method, 'amount' => (float) $sale->total_amount]];
            }

            $excess = array_sum(array_column($payments, 'amount')) - (float) $sale->total_amount;
            foreach ($payments as &$payment) {
                if ($excess > 0 && $payment['method'] === 'cash') {
                    $change = min($excess, $payment['amount']);
                    $payment['amount'] -= $change;
                    $excess -= $change;
                }
            }
            unset($payment);

            foreach ($payments as $payment) {
                $method = $payment['method'] ?: 'other';
                $totals[$method] ??= ['method' => $names[$method] ?? ucwords(str_replace('_', ' ', $method)), 'count' => 0, 'total' => 0];
                $totals[$method]['count']++;
                $totals[$method]['total'] += $payment['amount'];
            }
        }

        return collect($totals)->sortByDesc('total')->values()->toArray();
    }

    /**
     * Stock quantity of every product at a moment: today's stock minus every
     * recorded change after that moment (stock history and stock adjustments).
     */
    private function stockQuantitiesAt(Carbon $moment, $products): array
    {
        $current = $products->mapWithKeys(fn ($p) => [$p->id => (float) $p->current_stock])->toArray();
        if ($moment->gte(now())) {
            return $current;
        }

        $historyAfter = StockHistory::where('created_at', '>', $moment)
            ->groupBy('product_id')->selectRaw('product_id, SUM(quantity_change) as total')
            ->pluck('total', 'product_id');
        $adjustmentsAfter = StockAdjustment::where('created_at', '>', $moment)
            ->groupBy('product_id')
            ->selectRaw("product_id, SUM(CASE WHEN adjustment_type = 'add' THEN quantity ELSE -quantity END) as total")
            ->pluck('total', 'product_id');

        foreach ($current as $id => $qty) {
            $current[$id] = max(0, $qty - (float) ($historyAfter[$id] ?? 0) - (float) ($adjustmentsAfter[$id] ?? 0));
        }

        return $current;
    }

    private function lineRevenue($item): float
    {
        return (float) ($item->subtotal ?: $item->quantity * $item->price);
    }

    private function lineCost($item): float
    {
        return (float) $item->quantity * (float) ($item->purchase_cost ?? ($item->product->purchase_price ?? 0));
    }

    private function calculateDelta($current, $prior): ?float
    {
        if ((float) $prior == 0.0) {
            return null;
        }

        return round(($current - $prior) / abs($prior) * 100, 1);
    }

    private function getDateRange($rangeType): array
    {
        $today = now();

        return match ($rangeType) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'yesterday' => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            'this_week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'last_7_days' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'last_30_days' => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'last_year' => [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear()],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }

    private function getPriorDateRange($rangeType, Carbon $fromDate, Carbon $toDate): array
    {
        return match ($rangeType) {
            'today', 'yesterday' => [$fromDate->copy()->subDay(), $toDate->copy()->subDay()],
            'this_week' => [$fromDate->copy()->subWeek(), $toDate->copy()->subWeek()],
            'last_7_days' => [$fromDate->copy()->subDays(7), $toDate->copy()->subDays(7)],
            'last_30_days' => [$fromDate->copy()->subDays(30), $toDate->copy()->subDays(30)],
            'this_year', 'last_year' => [$fromDate->copy()->subYear()->startOfYear(), $fromDate->copy()->subYear()->endOfYear()],
            default => [$fromDate->copy()->subMonthNoOverflow()->startOfMonth(), $fromDate->copy()->subMonthNoOverflow()->endOfMonth()],
        };
    }
}
