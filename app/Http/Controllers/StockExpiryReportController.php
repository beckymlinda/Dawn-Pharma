<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseItem;
use App\Models\Product;
use Carbon\Carbon;

class StockExpiryReportController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseItem::with(['product', 'purchase.supplier'])
            ->where(function($q) {
                $q->whereNotNull('expiry_date')
                  ->orWhereNotNull('exp_date');
            })
            ->where('quantity', '>', 0);

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where(function($q) use ($request) {
                $q->where('expiry_date', '>=', $request->start_date)
                  ->orWhere('exp_date', '>=', $request->start_date);
            });
        }

        if ($request->filled('end_date')) {
            $query->where(function($q) use ($request) {
                $q->where('expiry_date', '<=', $request->end_date)
                  ->orWhere('exp_date', '<=', $request->end_date);
            });
        }

        // Filter by expiry status
        $expiryStatus = $request->get('expiry_status', 'all');
        $today = Carbon::today();

        switch ($expiryStatus) {
            case 'expired':
                $query->where(function($q) use ($today) {
                    $q->where('expiry_date', '<', $today)
                      ->orWhere('exp_date', '<', $today);
                });
                break;
            case 'expiring_soon':
                $query->where(function($q) use ($today) {
                    $q->where('expiry_date', '>=', $today)
                          ->where('expiry_date', '<=', $today->copy()->addDays(30))
                          ->orWhere('exp_date', '>=', $today)
                          ->where('exp_date', '<=', $today->copy()->addDays(30));
                });
                break;
            case 'expiring_60_days':
                $query->where(function($q) use ($today) {
                    $q->where('expiry_date', '>=', $today)
                          ->where('expiry_date', '<=', $today->copy()->addDays(60))
                          ->orWhere('exp_date', '>=', $today)
                          ->where('exp_date', '<=', $today->copy()->addDays(60));
                });
                break;
            case 'expiring_90_days':
                $query->where(function($q) use ($today) {
                    $q->where('expiry_date', '>=', $today)
                          ->where('expiry_date', '<=', $today->copy()->addDays(90))
                          ->orWhere('exp_date', '>=', $today)
                          ->where('exp_date', '<=', $today->copy()->addDays(90));
                });
                break;
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->whereHas('purchase', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }

        $expiryItems = $query->orderBy('expiry_date', 'asc')->paginate(50);

        // Get summary statistics
        $totalItems = $query->count();
        $expiredItems = $query->clone()->where(function($q) use ($today) {
            $q->where('expiry_date', '<', $today)
              ->orWhere('exp_date', '<', $today);
        })->count();
        $expiringSoonItems = $query->clone()
            ->where(function($q) use ($today) {
                $q->where('expiry_date', '>=', $today)
                      ->where('expiry_date', '<=', $today->copy()->addDays(30))
                      ->orWhere('exp_date', '>=', $today)
                      ->where('exp_date', '<=', $today->copy()->addDays(30));
            })->count();

        // Get products and suppliers for filters
        $products = Product::orderBy('name')->pluck('name', 'id');
        $suppliers = \App\Models\Contact::where('type', 'supplier')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('reports.stock-expiry', compact(
            'expiryItems',
            'totalItems',
            'expiredItems',
            'expiringSoonItems',
            'products',
            'suppliers'
        ));
    }

    public function exportCsv(Request $request)
    {
        $query = PurchaseItem::with(['product', 'purchase.supplier'])
            ->whereNotNull('expiry_date')
            ->where('quantity', '>', 0);

        // Apply same filters as index
        if ($request->filled('start_date')) {
            $query->where('expiry_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('expiry_date', '<=', $request->end_date);
        }

        $expiryStatus = $request->get('expiry_status', 'all');
        $today = Carbon::today();

        switch ($expiryStatus) {
            case 'expired':
                $query->where('expiry_date', '<', $today);
                break;
            case 'expiring_soon':
                $query->where('expiry_date', '>=', $today)
                      ->where('expiry_date', '<=', $today->copy()->addDays(30));
                break;
            case 'expiring_60_days':
                $query->where('expiry_date', '>=', $today)
                      ->where('expiry_date', '<=', $today->copy()->addDays(60));
                break;
            case 'expiring_90_days':
                $query->where('expiry_date', '>=', $today)
                      ->where('expiry_date', '<=', $today->copy()->addDays(90));
                break;
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('supplier_id')) {
            $query->whereHas('purchase', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }

        $items = $query->orderBy('expiry_date', 'asc')->get();

        $filename = "stock_expiry_report_" . date('Y-m-d') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($items) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'Product Name',
                'Supplier',
                'Purchase Date',
                'Expiry Date',
                'Days Until Expiry',
                'Quantity',
                'Unit Price (' . config('currency.symbol') . ')',
                'Total Value (' . config('currency.symbol') . ')',
                'Status'
            ]);

            // CSV Data
            foreach ($items as $item) {
                $expiryDate = $item->expiry_date ?: $item->exp_date;
                $daysUntilExpiry = $expiryDate ? Carbon::parse($expiryDate)->diffInDays(Carbon::today(), false) : null;
                $status = !$expiryDate ? 'No Expiry Date' : 
                         ($daysUntilExpiry < 0 ? 'Expired' : 
                         ($daysUntilExpiry <= 30 ? 'Expiring Soon' : 'Valid'));
                
                fputcsv($file, [
                    $item->product->name,
                    $item->purchase->supplier->name ?? 'N/A',
                    $item->purchase->purchase_date,
                    $expiryDate ? $expiryDate : 'Not Set',
                    $daysUntilExpiry ?? 'N/A',
                    $item->quantity,
                    number_format($item->unit_cost, 2),
                    number_format($item->quantity * $item->unit_cost, 2),
                    $status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
