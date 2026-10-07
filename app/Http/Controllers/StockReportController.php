<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockHistory;
use App\Models\Location;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        
        $query = Product::with(['category', 'brand', 'businessLocation'])
            ->where('current_stock', '>', 0);
            
        $products = $query->paginate($perPage);

        $locations = Location::all();

        // Calculate summary statistics (from all products, not just paginated)
        $allProducts = Product::where('current_stock', '>', 0)->get();
        $totalPurchaseValue = 0;
        $totalSaleValue = 0;

        foreach ($allProducts as $product) {
            $purchaseValue = $product->current_stock * $product->purchase_price;
            $saleValue = $product->current_stock * $product->selling_price;
            
            $totalPurchaseValue += $purchaseValue;
            $totalSaleValue += $saleValue;
        }

        $potentialProfit = $totalSaleValue - $totalPurchaseValue;
        $profitMargin = $totalPurchaseValue > 0 ? ($potentialProfit / $totalPurchaseValue) * 100 : 0;

        return view('pos.stock-report', compact(
            'products', 'locations', 'totalPurchaseValue', 
            'totalSaleValue', 'potentialProfit', 'profitMargin'
        ));
    }

    public function lowStock(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        
        $query = Product::with(['category', 'brand', 'businessLocation'])
            ->whereColumn('current_stock', '<=', 'alert_quantity');
            
        $products = $query->paginate($perPage);

        return view('pos.low-stock', compact('products'));
    }

    public function productStockHistory($productId)
    {
        $product = Product::findOrFail($productId);
        
        $histories = StockHistory::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();
            
        $quantitiesIn = $histories->where('quantity_change', '>', 0)->sum('quantity_change');
        $quantitiesOut = abs($histories->where('quantity_change', '<', 0)->sum('quantity_change'));
        $currentStock = $product->current_stock ?? 0;
        
        return view('pos.stock-history', compact('product', 'histories', 'quantitiesIn', 'quantitiesOut', 'currentStock'));
    }

    public function exportCsv()
    {
        $products = Product::with(['category', 'brand'])->get();
        
        $headers = [
            'SKU', 'Product', 'Category', 'Brand', 'Location', 
            'Unit Selling Price', 'Current Stock', 
            'Stock Value (Purchase)', 'Stock Value (Sale)'
        ];

        $csvData = [];
        foreach ($products as $product) {
            $purchaseValue = $product->current_stock * $product->purchase_price;
            $saleValue = $product->current_stock * $product->selling_price;
            
            $csvData[] = [
                $product->sku,
                $product->name,
                $product->category->name ?? 'N/A',
                $product->brand->name ?? 'N/A',
                $product->business_location,
                number_format($product->selling_price, 2),
                number_format($product->current_stock, 2),
                number_format($purchaseValue, 2),
                number_format($saleValue, 2)
            ];
        }

        $filename = 'stock_report_' . date('Y-m-d') . '.csv';
        
        $callback = function() use ($headers, $csvData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename);
    }

    public function exportExcel()
    {
        // For now, redirect to CSV export
        // In production, you would use Laravel Excel package
        return $this->exportCsv();
    }

    public function exportPdf()
    {
        // For now, redirect to print view
        // In production, you would use DomPDF or similar
        $products = Product::with(['category', 'brand'])->get();
        return view('pos.stock-report-pdf', compact('products'));
    }
}
