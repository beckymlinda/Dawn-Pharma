<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        // Determine date range based on request
        $dateRange = $request->input('date_range', 'this_month');

        if ($dateRange === 'custom' && $request->has('from_date') && $request->has('to_date')) {
            $fromDate = Carbon::createFromFormat('Y-m-d', $request->input('from_date'))->startOfDay();
            $toDate   = Carbon::createFromFormat('Y-m-d', $request->input('to_date'))->endOfDay();
        } else {
            [$fromDate, $toDate] = $this->getDateRange($dateRange);
        }

        // Keep clean copies for the view before any mutation
        $fromDateDisplay = $fromDate->copy()->format('Y-m-d');
        $toDateDisplay   = $toDate->copy()->format('Y-m-d');
        $fromDateLabel   = $fromDate->copy()->format('d M Y');
        $toDateLabel     = $toDate->copy()->format('d M Y');

        // ─── Fetch sale items in the date range ──────────────────────────
        $salesInRange = Sale::whereBetween('created_at', [$fromDate, $toDate])->get();

        $saleItemsInRange = SaleItem::with('product')
            ->whereHas('sale', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })
            ->get();

        // ─── Revenue ─────────────────────────────────────────────────────
        $totalSales = $saleItemsInRange->sum(function ($item) {
            return $item->subtotal ?: ($item->quantity * $item->price);
        });

        $totalTransactions = $salesInRange->count();

        // ─── Cost of Goods Sold (COGS) ───────────────────────────────────
        // Use the purchase_cost captured at time of sale if available, otherwise fall back to product purchase_price
        $costOfGoodsSold = $saleItemsInRange->sum(function ($item) {
            $unitCost = $item->purchase_cost ?? ($item->product->purchase_price ?? 0);
            return $item->quantity * $unitCost;
        });

        $totalItemsSold = $saleItemsInRange->sum('quantity');

        // ─── Gross Profit ────────────────────────────────────────────────
        $grossProfit  = $totalSales - $costOfGoodsSold;
        $grossMargin  = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 1) : 0;

        // ─── Expenses (placeholder – 0 if no expenses table) ─────────────
        $totalExpense = 0;

        // ─── Net Profit ──────────────────────────────────────────────────
        $netProfit  = $grossProfit - $totalExpense;
        $netMargin  = $totalSales > 0 ? round(($netProfit / $totalSales) * 100, 1) : 0;

        // ─── Stock Valuation ─────────────────────────────────────────────
        $products = Product::all();

        $closingStockCost  = $products->sum(fn($p) => $p->current_stock * $p->purchase_price);
        $closingStockRetail = $products->sum(fn($p) => $p->current_stock * $p->selling_price);
        $totalStockUnits   = $products->sum('current_stock');

        // Opening stock = closing stock + items sold in range
        $openingStockCost   = $closingStockCost + $costOfGoodsSold;
        $openingStockRetail = $closingStockRetail + $totalSales;

        // ─── Top Products by Profit ──────────────────────────────────────
        $productProfits = [];
        foreach ($saleItemsInRange as $item) {
            $pid  = $item->product_id;
            $name = $item->product->name ?? 'Unknown';
            $revenue = $item->subtotal ?: ($item->quantity * $item->price);
            $cost    = $item->quantity * ($item->purchase_cost ?? ($item->product->purchase_price ?? 0));
            $profit  = $revenue - $cost;

            if (!isset($productProfits[$pid])) {
                $productProfits[$pid] = [
                    'name'     => $name,
                    'qty'      => 0,
                    'revenue'  => 0,
                    'cost'     => 0,
                    'profit'   => 0,
                ];
            }
            $productProfits[$pid]['qty']     += $item->quantity;
            $productProfits[$pid]['revenue'] += $revenue;
            $productProfits[$pid]['cost']    += $cost;
            $productProfits[$pid]['profit']  += $profit;
        }

        // Sort by profit descending
        uasort($productProfits, fn($a, $b) => $b['profit'] <=> $a['profit']);
        $topProducts  = array_slice($productProfits, 0, 10, true);
        $lossProducts = array_filter($productProfits, fn($p) => $p['profit'] < 0);
        uasort($lossProducts, fn($a, $b) => $a['profit'] <=> $b['profit']);
        $lossProducts = array_slice($lossProducts, 0, 10, true);

        // ─── Payment Method Breakdown ────────────────────────────────────
        $paymentBreakdown = $salesInRange->groupBy('payment_method')->map(function ($group, $method) {
            return [
                'method' => $method,
                'count'  => $group->count(),
                'total'  => $group->sum('total_amount'),
            ];
        })->sortByDesc('total')->values()->toArray();

        // ─── Daily Trend (for the chart) ─────────────────────────────────
        $dailyTrend = $salesInRange->groupBy(function ($sale) {
            return $sale->created_at->format('Y-m-d');
        })->map(function ($group, $date) use ($saleItemsInRange) {
            $dayRevenue = $group->sum('total_amount');
            // Get sale IDs for this day
            $saleIds = $group->pluck('id')->toArray();
            $dayCost = $saleItemsInRange->whereIn('sale_id', $saleIds)->sum(function ($item) {
                return $item->quantity * ($item->purchase_cost ?? ($item->product->purchase_price ?? 0));
            });
            return [
                'date'    => Carbon::parse($date)->format('d M'),
                'revenue' => $dayRevenue,
                'cost'    => $dayCost,
                'profit'  => $dayRevenue - $dayCost,
            ];
        })->sortKeys()->values()->toArray();

        $currency = 'MWK';

        return view('pos.profit-loss', compact(
            'currency',
            'dateRange',
            'fromDateDisplay',
            'toDateDisplay',
            'fromDateLabel',
            'toDateLabel',
            'totalSales',
            'totalTransactions',
            'totalItemsSold',
            'costOfGoodsSold',
            'grossProfit',
            'grossMargin',
            'totalExpense',
            'netProfit',
            'netMargin',
            'openingStockCost',
            'openingStockRetail',
            'closingStockCost',
            'closingStockRetail',
            'totalStockUnits',
            'topProducts',
            'lossProducts',
            'paymentBreakdown',
            'dailyTrend'
        ));
    }

    /**
     * Helper function to get date range based on string input
     */
    private function getDateRange($rangeType)
    {
        $today = Carbon::now();

        switch ($rangeType) {
            case 'today':
                return [
                    $today->copy()->startOfDay(),
                    $today->copy()->endOfDay()
                ];
            case 'yesterday':
                return [
                    $today->copy()->subDay()->startOfDay(),
                    $today->copy()->subDay()->endOfDay()
                ];
            case 'last_7_days':
                return [
                    $today->copy()->subDays(7)->startOfDay(),
                    $today->copy()->endOfDay()
                ];
            case 'last_30_days':
                return [
                    $today->copy()->subDays(30)->startOfDay(),
                    $today->copy()->endOfDay()
                ];
            case 'this_month':
                return [
                    $today->copy()->startOfMonth(),
                    $today->copy()->endOfMonth()
                ];
            case 'last_month':
                return [
                    $today->copy()->subMonth()->startOfMonth(),
                    $today->copy()->subMonth()->endOfMonth()
                ];
            case 'this_month_last_year':
                return [
                    $today->copy()->subYear()->startOfMonth(),
                    $today->copy()->subYear()->endOfMonth()
                ];
            case 'this_year':
                return [
                    $today->copy()->startOfYear(),
                    $today->copy()->endOfYear()
                ];
            case 'last_year':
                return [
                    $today->copy()->subYear()->startOfYear(),
                    $today->copy()->subYear()->endOfYear()
                ];
            default:
                return [
                    $today->copy()->startOfMonth(),
                    $today->copy()->endOfMonth()
                ];
        }
    }
}
