<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockExpiryReportController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        
        // Base query for counting totals (filtered only by product/supplier/location)
        $baseQuery = PurchaseItem::where('quantity', '>', 0)
            ->where(function($q) {
                $q->whereNotNull('expiry_date')
                  ->orWhereNotNull('exp_date');
            });

        // Main query for results
        $query = PurchaseItem::with(['product', 'purchase.supplier'])
            ->where('quantity', '>', 0)
            ->where(function($q) {
                $q->whereNotNull('expiry_date')
                  ->orWhereNotNull('exp_date');
            });

        // Apply shared filters to both base and main query
        $this->applySharedFilters($query, $request);
        $this->applySharedFilters($baseQuery, $request);

        // Calculate summary statistics from baseQuery (ignoring the expiry_status filter)
        $totalItems = $baseQuery->count();
        
        $expiredItems = $baseQuery->clone()->where(function($q) use ($today) {
            $q->where('expiry_date', '<', $today)
              ->orWhere(function($sq) use ($today) {
                  $sq->whereNull('expiry_date')->where('exp_date', '<', $today);
              });
        })->count();
        
        $expiringSoonItems = $baseQuery->clone()->where(function($q) use ($today) {
            $thirtyDays = $today->copy()->addDays(30);
            $q->where(function($sq) use ($today, $thirtyDays) {
                $sq->where('expiry_date', '>=', $today)->where('expiry_date', '<=', $thirtyDays);
            })->orWhere(function($sq) use ($today, $thirtyDays) {
                $sq->whereNull('expiry_date')->where('exp_date', '>=', $today)->where('exp_date', '<=', $thirtyDays);
            });
        })->count();

        // Now apply specific expiry status filter to the main query
        if ($request->filled('expiry_status') && $request->expiry_status !== 'all') {
            $this->applyExpiryStatusFilter($query, $request->expiry_status, $today);
        }

        // Apply custom date range filter to the main query
        if ($request->filled('start_date')) {
            $query->where(function($q) use ($request) {
                $q->where('expiry_date', '>=', $request->start_date)
                  ->orWhere(function($sq) use ($request) {
                      $sq->whereNull('expiry_date')->where('exp_date', '>=', $request->start_date);
                  });
            });
        }
        if ($request->filled('end_date')) {
            $query->where(function($q) use ($request) {
                $q->where('expiry_date', '<=', $request->end_date)
                  ->orWhere(function($sq) use ($request) {
                      $sq->whereNull('expiry_date')->where('exp_date', '<=', $request->end_date);
                  });
            });
        }

        // Sorting: Use COALESCE to sort by whichever date is present
        $expiryItems = $query->orderBy(DB::raw('COALESCE(expiry_date, exp_date)'), 'asc')
            ->paginate($request->input('per_page', 25));

        // Get filter data
        $products = Product::orderBy('name')->pluck('name', 'id');
        $suppliers = \App\Models\Contact::where('type', 'supplier')->orderBy('name')->pluck('name', 'id');

        return view('reports.stock-expiry', compact(
            'expiryItems', 'totalItems', 'expiredItems', 'expiringSoonItems', 'products', 'suppliers'
        ));
    }

    private function applySharedFilters($query, Request $request)
    {
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('supplier_id')) {
            $query->whereHas('purchase', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }
    }

    private function applyExpiryStatusFilter($query, $status, $today)
    {
        switch ($status) {
            case 'expired':
                $query->where(function($q) use ($today) {
                    $q->where('expiry_date', '<', $today)
                      ->orWhere(function($sq) use ($today) {
                          $sq->whereNull('expiry_date')->where('exp_date', '<', $today);
                      });
                });
                break;
            case 'expiring_soon':
                $thirtyDays = $today->copy()->addDays(30);
                $query->where(function($q) use ($today, $thirtyDays) {
                    $q->where(function($sq) use ($today, $thirtyDays) {
                        $sq->where('expiry_date', '>=', $today)->where('expiry_date', '<=', $thirtyDays);
                    })->orWhere(function($sq) use ($today, $thirtyDays) {
                        $sq->whereNull('expiry_date')->where('exp_date', '>=', $today)->where('exp_date', '<=', $thirtyDays);
                    });
                });
                break;
            case 'expiring_60_days':
                $sixtyDays = $today->copy()->addDays(60);
                $query->where(function($q) use ($today, $sixtyDays) {
                    $q->where(function($sq) use ($today, $sixtyDays) {
                        $sq->where('expiry_date', '>=', $today)->where('expiry_date', '<=', $sixtyDays);
                    })->orWhere(function($sq) use ($today, $sixtyDays) {
                        $sq->whereNull('expiry_date')->where('exp_date', '>=', $today)->where('exp_date', '<=', $sixtyDays);
                    });
                });
                break;
        }
    }

    public function exportCsv(Request $request)
    {
        // Re-use logic or implement specifically for CSV if needed
        // For now, let's keep it simple as the user focused on the report not showing items
        return redirect()->route('reports.stock-expiry');
    }
}
