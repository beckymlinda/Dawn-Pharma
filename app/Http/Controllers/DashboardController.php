<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get current date ranges
        $today = Carbon::today();
        $thirtyDaysAgo = Carbon::today()->subDays(30);
        
        // Calculate real statistics using existing columns
        $totalSales = Sale::whereDate('created_at', $today)->sum('total_amount');
        $netSales = $totalSales; // You can modify this based on your business logic
        $invoiceDue = Sale::where('payment_status', 'due')->whereDate('created_at', $today)->sum('sell_due');
        $sellReturn = 0; // No return_type column, set to 0 for now
        $totalPurchase = Purchase::whereDate('purchase_date', $today)->sum('grand_total');
        $purchaseDue = Purchase::where('payment_status', 'due')->whereDate('purchase_date', $today)->sum('grand_total');
        $purchaseReturn = 0; // No return_type column, set to 0 for now
        $expense = 0; // No expense table yet, set to 0 for now
        
        // Calculate expiry items (products expiring in next 30 days)
        $thirtyDaysFromNow = Carbon::today()->addDays(30);
        $expiryItems = Product::where('expiry_date', '<=', $thirtyDaysFromNow)
                            ->where('expiry_date', '>=', $today)
                            ->count();
        
        // Get sales data for the last 30 days
        $salesData = Sale::where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        // Prepare chart data
        $labels = [];
        $data = [];
        
        // Fill missing dates with 0 for last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::today()->subDays($i)->format('M d');
            
            $daySales = $salesData->where('date', $date)->first();
            $data[] = $daySales ? $daySales->total : 0;
        }
        
        // Calculate low stock items
        $lowStockItems = Product::whereColumn('current_stock', '<=', 'alert_quantity')->count();
        
        return view('pos.dashboard', compact(
            'totalSales',
            'netSales', 
            'invoiceDue',
            'sellReturn',
            'totalPurchase',
            'purchaseDue',
            'purchaseReturn',
            'expense',
            'expiryItems',
            'lowStockItems',
            'labels',
            'data'
        ));
    }
}
