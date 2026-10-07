<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    /**
     * List products with search and pagination.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'unit'])
            ->withSum('saleItems as total_sold', 'quantity')
            ->withSum('purchaseItems as total_purchased', 'quantity')
            ->withMax('saleItems as last_sold_at', 'created_at')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $products = $query->paginate(25)->withQueryString();

        return view('reports.products.index', compact('products'));
    }

    /**
     * Full history of a single product: sales, purchases, adjustments,
     * stock movements and activity, each with the user who did it.
     */
    public function show(Request $request, Product $product)
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;
        $activeTab = in_array($request->input('tab'), ['sales', 'purchases', 'adjustments', 'movements', 'activity'])
            ? $request->input('tab')
            : 'sales';

        $inRange = function ($query, string $column) use ($from, $to) {
            if ($from) {
                $query->where($column, '>=', $from);
            }
            if ($to) {
                $query->where($column, '<=', $to);
            }
            return $query;
        };

        // Sales
        $salesQuery = SaleItem::where('sale_items.product_id', $product->id)
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id');
        $inRange($salesQuery, 'sales.created_at');

        $salesTotals = (clone $salesQuery)
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as qty, COALESCE(SUM(sale_items.subtotal), 0) as amount, COUNT(DISTINCT sales.id) as count')
            ->first();
        $salesPerDay = (clone $salesQuery)
            ->selectRaw('DATE(sales.created_at) as day, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as amount')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $sales = $salesQuery
            ->select('sale_items.*', 'sales.created_at as sold_at')
            ->with(['sale.user', 'sale.register.user', 'sale.editor'])
            ->orderByDesc('sales.created_at')
            ->paginate(25, ['*'], 'sales_page')
            ->appends($request->except('sales_page') + ['tab' => 'sales']);

        // Purchases
        $purchasesQuery = PurchaseItem::where('purchase_items.product_id', $product->id)
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id');
        $inRange($purchasesQuery, 'purchases.purchase_date');

        $purchaseTotals = (clone $purchasesQuery)
            ->selectRaw('COALESCE(SUM(purchase_items.quantity), 0) as qty, COALESCE(SUM(purchase_items.line_total), 0) as amount, COUNT(DISTINCT purchases.id) as count')
            ->first();
        $purchasesPerDay = (clone $purchasesQuery)
            ->selectRaw('DATE(purchases.purchase_date) as day, SUM(purchase_items.quantity) as qty, SUM(purchase_items.line_total) as amount')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $purchases = $purchasesQuery
            ->select('purchase_items.*', 'purchases.purchase_date as purchased_at')
            ->with(['purchase.user', 'purchase.supplier'])
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchase_items.id')
            ->paginate(25, ['*'], 'purchases_page')
            ->appends($request->except('purchases_page') + ['tab' => 'purchases']);

        // Stock adjustments
        $adjustments = $inRange(StockAdjustment::with('user')->where('product_id', $product->id), 'created_at')
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'adjustments_page')
            ->appends($request->except('adjustments_page') + ['tab' => 'adjustments']);

        // Stock movements (opening stock, purchases, sales, ...)
        $movements = $inRange(StockHistory::with('user')->where('product_id', $product->id), 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'movements_page')
            ->appends($request->except('movements_page') + ['tab' => 'movements']);

        // Product activity log (created / updated / deleted)
        $activities = $inRange(
            ActivityLog::with('user')->where('subject_type', Product::class)->where('subject_id', $product->id),
            'created_at'
        )
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'activity_page')
            ->appends($request->except('activity_page') + ['tab' => 'activity']);

        $product->load(['category', 'brand', 'unit', 'supplier']);

        return view('reports.products.show', compact(
            'product', 'activeTab', 'sales', 'salesTotals', 'salesPerDay',
            'purchases', 'purchaseTotals', 'purchasesPerDay',
            'adjustments', 'movements', 'activities'
        ));
    }
}
