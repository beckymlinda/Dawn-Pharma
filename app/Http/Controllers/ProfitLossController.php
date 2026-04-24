<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        // Determine date range based on request
        if ($request->has('date_range') && $request->input('date_range') !== 'custom') {
            [$fromDate, $toDate] = $this->getDateRange($request->input('date_range'));
        } elseif ($request->has('from_date') && $request->has('to_date')) {
            // Custom date range
            $fromDate = Carbon::createFromFormat('Y-m-d', $request->input('from_date'));
            $toDate = Carbon::createFromFormat('Y-m-d', $request->input('to_date'));
        } else {
            // Default to current month
            $fromDate = Carbon::now()->startOfMonth();
            $toDate = Carbon::now()->endOfMonth();
        }

        // Get all sales items in the date range
        $saleItemsInRange = SaleItem::with('product')
            ->whereHas('sale', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);
            })
            ->get();

        // Get all products for stock calculations
        $products = Product::all();

        // ======================= LEFT SIDE CALCULATIONS =======================
        
        // Opening Stock calculations
        $openingStockQuantity = 0;
        $openingStockBySalePrice = 0;
        
        foreach ($products as $product) {
            // Calculate how many items were sold in the date range
            $soldInRange = SaleItem::where('product_id', $product->id)
                ->whereHas('sale', function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);
                })
                ->sum('quantity');
            
            // Opening stock = current stock + sold in range
            $openingQty = $product->current_stock + $soldInRange;
            
            $openingStockQuantity += $openingQty * $product->purchase_price;
            $openingStockBySalePrice += $openingQty * $product->selling_price;
        }

        // Total Purchase
        $totalPurchase = Purchase::whereBetween('purchase_date', [$fromDate->startOfDay(), $toDate->endOfDay()])
            ->sum('grand_total');

        // Total Expense (return 0 if no expense table)
        $totalExpense = 0;

        // Total Sell Discount
        $totalSellDiscount = 0; // Can be calculated if discount field exists in sales

        // Total Sell Return (return 0 if not available)
        $totalSellReturn = 0;

        // ======================= RIGHT SIDE CALCULATIONS =======================
        
        // Closing Stock calculations
        $closingStockPurchasePrice = 0;
        $closingStockSalePrice = 0;
        
        foreach ($products as $product) {
            $closingStockPurchasePrice += $product->current_stock * $product->purchase_price;
            $closingStockSalePrice += $product->current_stock * $product->selling_price;
        }

        // Total Sales (exclude tax from sale_items.price if tax is included)
        $totalSales = $saleItemsInRange->sum('subtotal');
        
        // If subtotal doesn't exist, calculate from quantity * price
        if ($totalSales == 0) {
            $totalSales = $saleItemsInRange->sum(function ($item) {
                return $item->quantity * $item->price;
            });
        }

        // Cost of Goods Sold
        $costOfGoodsSold = $saleItemsInRange->sum(function ($item) {
            return $item->quantity * ($item->purchase_cost ?? 0);
        });

        // Gross Profit
        $grossProfit = $totalSales - $costOfGoodsSold;

        // Net Profit (Gross Profit - Total Expense - Total Sell Discount + Total Sell Return)
        $netProfit = $grossProfit - $totalExpense - $totalSellDiscount + $totalSellReturn;

        // Format currency helper
        $currency = config('currency.symbol', 'MWK');

        // Split left and right items for the view
        $leftItems = [
            ['label' => 'Opening Stock', 'sub' => '(By purchase price):', 'value' => $openingStockQuantity],
            ['label' => 'Opening Stock', 'sub' => '(By sale price):', 'value' => $openingStockBySalePrice],
            ['label' => 'Total Purchase', 'sub' => '(Exc. tax, Discount)', 'value' => $totalPurchase],
            ['label' => 'Total Expense', 'value' => $totalExpense],
            ['label' => 'Total Sell Discount', 'value' => $totalSellDiscount],
            ['label' => 'Total Sell Return', 'value' => $totalSellReturn],
        ];

        $rightItems = [
            ['label' => 'Closing Stock', 'sub' => '(By purchase price):', 'value' => $closingStockPurchasePrice],
            ['label' => 'Closing Stock', 'sub' => '(By sale price):', 'value' => $closingStockSalePrice],
            ['label' => 'Total Sales', 'sub' => '(Exc. tax, Discount)', 'value' => $totalSales],
            ['label' => 'Cost of Goods Sold', 'value' => $costOfGoodsSold],
            ['label' => 'Gross Profit', 'value' => $grossProfit],
            ['label' => 'Net Profit', 'value' => $netProfit],
        ];

        return view('pos.profit-loss', [
            'leftItems' => $leftItems,
            'rightItems' => $rightItems,
            'currency' => $currency,
            'fromDate' => $fromDate->format('Y-m-d'),
            'toDate' => $toDate->format('Y-m-d'),
            'dateRange' => $request->has('date_range') ? $request->input('date_range') : 'this_month',
        ]);
    }

    /**
     * Helper function to get date range based on string input
     */
    private function getDateRange($rangeType)
    {
        $today = Carbon::now();
        
        switch ($rangeType) {
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
