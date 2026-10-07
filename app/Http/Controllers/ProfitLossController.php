<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $dateRange = $request->input('date_range', 'this_month');

        if ($dateRange === 'custom' && $request->has('from_date') && $request->has('to_date')) {
            $fromDate = Carbon::createFromFormat('Y-m-d', $request->input('from_date'))->startOfDay();
            $toDate   = Carbon::createFromFormat('Y-m-d', $request->input('to_date'))->endOfDay();
            $diffInDays = $fromDate->diffInDays($toDate);
            $priorFromDate = $fromDate->copy()->subDays($diffInDays + 1);
            $priorToDate = $fromDate->copy()->subSeconds(1);
        } else {
            [$fromDate, $toDate] = $this->getDateRange($dateRange);
            [$priorFromDate, $priorToDate] = $this->getPriorDateRange($dateRange, $fromDate, $toDate);
        }

        $fromDateDisplay = $fromDate->copy()->format('Y-m-d');
        $toDateDisplay   = $toDate->copy()->format('Y-m-d');
        $fromDateLabel   = $fromDate->copy()->format('d M Y');
        $toDateLabel     = $toDate->copy()->format('d M Y');

        // Current Period Metrics
        $currentMetrics = $this->calculateMetrics($fromDate, $toDate);
        
        // Prior Period Metrics for Deltas
        $priorMetrics = $this->calculateMetrics($priorFromDate, $priorToDate);

        // Calculate Deltas
        $deltas = [
            'revenue' => $this->calculateDelta($currentMetrics['totalSales'], $priorMetrics['totalSales']),
            'gross_profit' => $this->calculateDelta($currentMetrics['grossProfit'], $priorMetrics['grossProfit']),
            'net_profit' => $this->calculateDelta($currentMetrics['netProfit'], $priorMetrics['netProfit']),
            'transactions' => $this->calculateDelta($currentMetrics['totalTransactions'], $priorMetrics['totalTransactions']),
        ];

        // Stock Valuation & Health
        $products = Product::all();
        $closingStockCost  = $products->sum(fn($p) => $p->current_stock * $p->purchase_price);
        $closingStockRetail = $products->sum(fn($p) => $p->current_stock * $p->selling_price);
        $totalStockUnits   = $products->sum('current_stock');

        // Optimizing sold quantities query to avoid N+1 queries (since products can be thousands)
        $soldQuantities = SaleItem::whereHas('sale', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);
            })
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->pluck('total_sold', 'product_id')
            ->toArray();

        // Calculate Opening Stock
        $openingStockQuantity = 0;
        $openingStockBySalePrice = 0;
        foreach ($products as $product) {
            $soldInRange = $soldQuantities[$product->id] ?? 0;
            $openingQty = $product->current_stock + $soldInRange;
            
            $openingStockQuantity += $openingQty * $product->purchase_price;
            $openingStockBySalePrice += $openingQty * $product->selling_price;
        }

        // Total Purchase
        $totalPurchase = Purchase::whereBetween('purchase_date', [$fromDate->startOfDay(), $toDate->endOfDay()])
            ->sum('grand_total');

        $totalExpense = 0;
        $totalSellDiscount = 0;
        $totalSellReturn = 0;

        $leftItems = [
            ['label' => 'Opening Stock', 'sub' => '(By purchase price):', 'value' => $openingStockQuantity],
            ['label' => 'Opening Stock', 'sub' => '(By sale price):', 'value' => $openingStockBySalePrice],
            ['label' => 'Total Purchase', 'sub' => '(Exc. tax, Discount)', 'value' => $totalPurchase],
            ['label' => 'Total Expense', 'value' => $totalExpense],
            ['label' => 'Total Sell Discount', 'value' => $totalSellDiscount],
            ['label' => 'Total Sell Return', 'value' => $totalSellReturn],
        ];

        $rightItems = [
            ['label' => 'Closing Stock', 'sub' => '(By purchase price):', 'value' => $closingStockCost],
            ['label' => 'Closing Stock', 'sub' => '(By sale price):', 'value' => $closingStockRetail],
            ['label' => 'Total Sales', 'sub' => '(Exc. tax, Discount)', 'value' => $currentMetrics['totalSales']],
            ['label' => 'Cost of Goods Sold', 'value' => $currentMetrics['costOfGoodsSold']],
            ['label' => 'Gross Profit', 'value' => $currentMetrics['grossProfit']],
            ['label' => 'Net Profit', 'value' => $currentMetrics['netProfit']],
        ];

        // Inventory Turnover Ratio = COGS / Average Inventory Cost (Approx: Closing Stock Cost)
        $inventoryTurnover = $closingStockCost > 0 ? ($currentMetrics['costOfGoodsSold'] / $closingStockCost) : 0;
        
        // Slow Moving Items (0 sales in last 30 days)
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $recentSaleProductIds = SaleItem::whereHas('sale', function ($query) use ($thirtyDaysAgo) {
            $query->where('created_at', '>=', $thirtyDaysAgo);
        })->pluck('product_id')->unique();

        $slowMovingProducts = Product::where('current_stock', '>', 0)
            ->whereNotIn('id', $recentSaleProductIds)
            ->orderBy('current_stock', 'desc')
            ->take(10)
            ->get();

        // Top Categories by Gross Margin
        $categoryMargins = [];
        $saleItemsWithCategories = SaleItem::with('product.category')
            ->whereHas('sale', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })->get();
            
        foreach ($saleItemsWithCategories as $item) {
            $catName = $item->product->category->name ?? 'Uncategorized';
            $revenue = $item->subtotal ?: ($item->quantity * $item->price);
            $cost = $item->quantity * ($item->purchase_cost ?? ($item->product->purchase_price ?? 0));
            
            if (!isset($categoryMargins[$catName])) {
                $categoryMargins[$catName] = ['revenue' => 0, 'cost' => 0];
            }
            $categoryMargins[$catName]['revenue'] += $revenue;
            $categoryMargins[$catName]['cost'] += $cost;
        }
        
        foreach ($categoryMargins as $cat => &$data) {
            $profit = $data['revenue'] - $data['cost'];
            $data['margin'] = $data['revenue'] > 0 ? round(($profit / $data['revenue']) * 100, 1) : 0;
        }
        uasort($categoryMargins, fn($a, $b) => $b['margin'] <=> $a['margin']);
        $categoryMargins = array_slice($categoryMargins, 0, 5, true);

        // Six Month Seasonality Trend
        $sixMonthsAgo = Carbon::now()->startOfMonth()->subMonths(5);
        $sixMonthSales = Sale::where('created_at', '>=', $sixMonthsAgo)->get();
        $sixMonthSaleItems = SaleItem::with('product')->whereHas('sale', function($q) use($sixMonthsAgo) {
            $q->where('created_at', '>=', $sixMonthsAgo);
        })->get();

        $seasonalityTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->startOfMonth()->subMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $monthKey = $monthStart->format('M Y');
            
            $monthSales = $sixMonthSales->whereBetween('created_at', [$monthStart, $monthEnd]);
            $monthRevenue = $monthSales->sum('total_amount');
            
            $saleIds = $monthSales->pluck('id')->toArray();
            $monthCost = $sixMonthSaleItems->whereIn('sale_id', $saleIds)->sum(function ($item) {
                return $item->quantity * ($item->purchase_cost ?? ($item->product->purchase_price ?? 0));
            });
            
            $seasonalityTrend[] = [
                'month' => $monthKey,
                'revenue' => $monthRevenue,
                'expenses' => $monthCost, // COGS only
                'net_profit' => $monthRevenue - $monthCost
            ];
        }

        $currency = 'MWK';

        return view('pos.profit-loss', array_merge([
            'currency' => $currency,
            'dateRange' => $dateRange,
            'fromDateDisplay' => $fromDateDisplay,
            'toDateDisplay' => $toDateDisplay,
            'fromDateLabel' => $fromDateLabel,
            'toDateLabel' => $toDateLabel,
            'deltas' => $deltas,
            'closingStockCost' => $closingStockCost,
            'closingStockRetail' => $closingStockRetail,
            'totalStockUnits' => $totalStockUnits,
            'inventoryTurnover' => $inventoryTurnover,
            'slowMovingProducts' => $slowMovingProducts,
            'categoryMargins' => $categoryMargins,
            'seasonalityTrend' => $seasonalityTrend,
            'leftItems' => $leftItems,
            'rightItems' => $rightItems
        ], $currentMetrics));
    }

    private function calculateMetrics($start, $end)
    {
        $sales = Sale::whereBetween('created_at', [$start, $end])->get();
        $saleItems = SaleItem::with('product')
            ->whereHas('sale', function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })->get();

        $totalSales = $sales->sum('total_amount');
        $totalTransactions = $sales->count();
        $totalItemsSold = $saleItems->sum('quantity');

        $costOfGoodsSold = $saleItems->sum(function ($item) {
            $unitCost = $item->purchase_cost ?? ($item->product->purchase_price ?? 0);
            return $item->quantity * $unitCost;
        });

        $grossProfit = $totalSales - $costOfGoodsSold;
        $grossMargin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 1) : 0;

        // No operating expenses in current system
        $operatingExpenses = 0; 
        
        $netProfit = $grossProfit;
        $netMargin = $grossMargin;

        $paymentBreakdown = $sales->groupBy('payment_method')->map(function ($group, $method) {
            return [
                'method' => $method,
                'count'  => $group->count(),
                'total'  => $group->sum('total_amount'),
            ];
        })->sortByDesc('total')->values()->toArray();

        return [
            'totalSales' => $totalSales,
            'totalTransactions' => $totalTransactions,
            'totalItemsSold' => $totalItemsSold,
            'costOfGoodsSold' => $costOfGoodsSold,
            'grossProfit' => $grossProfit,
            'grossMargin' => $grossMargin,
            'operatingExpenses' => $operatingExpenses,
            'netProfit' => $netProfit,
            'netMargin' => $netMargin,
            'paymentBreakdown' => $paymentBreakdown
        ];
    }

    private function calculateDelta($current, $prior)
    {
        if ($prior == 0) return $current > 0 ? 100 : 0;
        return round((($current - $prior) / abs($prior)) * 100, 1);
    }

    private function getDateRange($rangeType)
    {
        $today = Carbon::now();
        switch ($rangeType) {
            case 'today': return [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
            case 'yesterday': return [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()];
            case 'last_7_days': return [$today->copy()->subDays(7)->startOfDay(), $today->copy()->endOfDay()];
            case 'last_30_days': return [$today->copy()->subDays(30)->startOfDay(), $today->copy()->endOfDay()];
            case 'this_month': return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
            case 'last_month': return [$today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->endOfMonth()];
            case 'this_year': return [$today->copy()->startOfYear(), $today->copy()->endOfYear()];
            case 'last_year': return [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear()];
            default: return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
        }
    }

    private function getPriorDateRange($rangeType, $fromDate, $toDate)
    {
        switch ($rangeType) {
            case 'today': return [$fromDate->copy()->subDay(), $toDate->copy()->subDay()];
            case 'yesterday': return [$fromDate->copy()->subDay(), $toDate->copy()->subDay()];
            case 'last_7_days': return [$fromDate->copy()->subDays(7), $toDate->copy()->subDays(7)];
            case 'last_30_days': return [$fromDate->copy()->subDays(30), $toDate->copy()->subDays(30)];
            case 'this_month': return [$fromDate->copy()->subMonth()->startOfMonth(), $fromDate->copy()->subMonth()->endOfMonth()];
            case 'last_month': return [$fromDate->copy()->subMonth()->startOfMonth(), $fromDate->copy()->subMonth()->endOfMonth()];
            case 'this_year': return [$fromDate->copy()->subYear()->startOfYear(), $fromDate->copy()->subYear()->endOfYear()];
            case 'last_year': return [$fromDate->copy()->subYear()->startOfYear(), $fromDate->copy()->subYear()->endOfYear()];
            default: return [$fromDate->copy()->subMonth()->startOfMonth(), $fromDate->copy()->subMonth()->endOfMonth()];
        }
    }
}
