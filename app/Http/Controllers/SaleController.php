<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use App\Models\Payment;
use App\Helpers\PaymentMethods;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        // Start with base query
        $query = Sale::with('items.product', 'payments');
        
        $period = $request->input('period', 'all');
        
        // Prioritize custom date range if provided
        if ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                $query->where('created_at', '>=', $startDate);
            }
            if ($request->filled('end_date')) {
                $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
                $query->where('created_at', '<=', $endDate);
            }
        } else {
            // Otherwise apply period filter
            [$periodStart, $periodEnd] = $this->resolvePeriodDates($period);
            
            if ($periodStart && $periodEnd) {
                $query->where('created_at', '>=', $periodStart)
                      ->where('created_at', '<=', $periodEnd);
            }
        }
        
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        if ($request->filled('user_id')) {
            $query->where('edited_by', $request->user_id);
        }
        
        if ($request->filled('register_id')) {
            $query->where('register_id', $request->register_id);
        }
        
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('invoice_no', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('contact_number', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Get filtered sales with pagination
        $sales = $query->latest()->paginate(15);
        
        // Calculate summary from paginated sales
        // Calculate summary using the same logic as the query
        $summary = $this->calculateSalesSummary($sales->getCollection(), $request, $period);
        
        // Get all users for filter dropdown
        $users = User::orderBy('name')->get();
        
        // Get all registers for filter dropdown
        $registers = \App\Models\Register::with('user')->orderBy('opened_at', 'desc')->get();
        
        return view('pos.sales', compact('sales', 'summary', 'users', 'registers'));
    }
    
    private function calculateSalesSummary($sales, $request, $period = 'all')
    {
        $summary = [];
        
        // Start with base query for sales totals
        $query = \App\Models\Sale::query();
        
        // Prioritize custom date range for summary (must match index filtering)
        if ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                $startDateTime = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                $query->where('created_at', '>=', $startDateTime);
            }
            
            if ($request->filled('end_date')) {
                $endDateTime = \Carbon\Carbon::parse($request->end_date)->endOfDay();
                $query->where('created_at', '<=', $endDateTime);
            }
        } elseif ($request->filled('period') || $period) {
            $periodToUse = $request->input('period', $period);
            [$periodStart, $periodEnd] = $this->resolvePeriodDates($periodToUse);
            if ($periodStart && $periodEnd) {
                $query->where('created_at', '>=', $periodStart)
                      ->where('created_at', '<=', $periodEnd);
            }
        }
        
        // Apply same filters as the main query
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('invoice_no', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Get all filtered sales for summary
        $filteredSales = $query->get();
        
        if ($request->filled('payment_method')) {
            // Filtered summary for specific payment method
            $summary['filtered_count'] = $filteredSales->count();
            $summary['filtered_total'] = $filteredSales->sum('total_amount');
            
            // Still need the structure for the view loop if necessary
            $summary['payment_methods'] = [
                $request->payment_method => [
                    'count' => $filteredSales->count(),
                    'total' => $filteredSales->sum('total_amount')
                ]
            ];
        } else {
            // Summary for all payment methods using sales table
            $paymentMethods = PaymentMethods::getAllMethods();
            
            $paymentSummary = [];
            foreach ($paymentMethods as $method) {
                $methodSales = $filteredSales->where('payment_method', $method);
                $paymentSummary[$method] = [
                    'count' => $methodSales->count(),
                    'total' => $methodSales->sum('total_amount')
                ];
            }
            
            $summary['payment_methods'] = $paymentSummary;
        }
        
        return $summary;
    }

    private function resolvePeriodDates($period)
    {
        $today = \Carbon\Carbon::now();
        switch ($period) {
            case 'all':
                return [null, null];
            case 'today':
                return [
                    $today->copy()->startOfDay(),
                    $today->copy()->endOfDay(),
                ];
            case 'yesterday':
                return [
                    $today->copy()->subDay()->startOfDay(),
                    $today->copy()->subDay()->endOfDay(),
                ];
            case 'this_week':
                return [
                    $today->copy()->startOfWeek(),
                    $today->copy()->endOfWeek(),
                ];
            case 'last_week':
                return [
                    $today->copy()->subWeek()->startOfWeek(),
                    $today->copy()->subWeek()->endOfWeek(),
                ];
            case 'this_month':
                return [
                    $today->copy()->startOfMonth(),
                    $today->copy()->endOfMonth(),
                ];
            case 'last_month':
                return [
                    $today->copy()->subMonth()->startOfMonth(),
                    $today->copy()->subMonth()->endOfMonth(),
                ];
            case 'this_year':
                return [
                    $today->copy()->startOfYear(),
                    $today->copy()->endOfYear(),
                ];
            case 'last_year':
                return [
                    $today->copy()->subYear()->startOfYear(),
                    $today->copy()->subYear()->endOfYear(),
                ];
            default:
                return [null, null];
        }
    }

    public function create()
    {
        $products = Product::with('brand', 'category')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        return view('pos.pos', compact('products', 'categories', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_no' => 'required|string|unique:sales,invoice_no',
            'customer_name' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'location' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'total_amount' => 'required|numeric',
            'total_paid' => 'required|numeric',
            'sell_due' => 'nullable|numeric',
            'total_items' => 'required|integer',
            'items' => 'required|array'
        ]);

        $sale = Sale::create($data);

        foreach ($data['items'] as $it) {
            $product = Product::find($it['product_id']);
            $subtotal = $it['quantity'] * $it['price'];
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $it['product_id'],
                'quantity' => $it['quantity'],
                'price' => $it['price'],
                'subtotal' => $subtotal,
            ]);

            if ($product) {
                $product->current_stock = max(0, $product->current_stock - $it['quantity']);
                $product->save();
            }
        }

        // Create payment record (Crucial for summary accuracy)
        Payment::create([
            'sale_id' => $sale->id,
            'method' => $data['payment_method'] ?? 'cash',
            'amount' => $data['total_paid'] ?? $data['total_amount']
        ]);

        return redirect()->route('sales.show', $sale->id);
    }

    public function show($id)
    {
        $sale = Sale::with('items.product')->findOrFail($id);
        return view('pos.sale-show', compact('sale'));
    }

    public function edit($id)
    {
        $sale = Sale::with('items.product')->findOrFail($id);
        $products = Product::with('brand', 'category')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        
        return view('pos.sale-edit', compact('sale', 'products', 'categories', 'brands'));
    }

    public function update(Request $request, $id)
    {
        try {
            $sale = Sale::findOrFail($id);
            
            $validated = $request->validate([
                'customer_name' => 'nullable|string',
                'contact_number' => 'nullable|string',
                'payment_method' => 'required|string',
                'payment_status' => 'required|string',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.price' => 'required|numeric|min:0'
            ]);

            // Calculate original totals for edit log
            $originalTotal = $sale->total_amount;
            $originalItems = $sale->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal
                ];
            })->toArray();

            // Calculate new totals
            $newTotal = 0;
            $newItems = [];
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $subtotal = $item['quantity'] * $item['price'];
                $newTotal += $subtotal;
                
                $newItems[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal
                ];
            }

            // Create edit log
            $editLog = [
                'original_total' => $originalTotal,
                'new_total' => $newTotal,
                'total_change' => $newTotal - $originalTotal,
                'original_payment_method' => $sale->payment_method,
                'new_payment_method' => $validated['payment_method'],
                'original_items' => $originalItems,
                'new_items' => $newItems,
                'edited_by' => auth()->user()->name,
                'edited_at' => now()->format('Y-m-d H:i:s')
            ];

            // Restore original stock
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->current_stock += $item->quantity;
                    $product->save();
                }
            }

            // Update sale
            $sale->update([
                'customer_name' => $validated['customer_name'] ?? $sale->customer_name,
                'contact_number' => $validated['contact_number'] ?? $sale->contact_number,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'],
                'total_amount' => $newTotal,
                'total_paid' => $newTotal,
                'sell_due' => 0,
                'total_items' => collect($validated['items'])->sum('quantity'),
                'is_edited' => true,
                'edit_log' => json_encode($editLog),
                'edited_at' => now(),
                'edited_by' => auth()->id()
            ]);

            // Delete old items
            $sale->items()->delete();
            
            // Delete old payments and create new ones
            $sale->payments()->delete();
            Payment::create([
                'sale_id' => $sale->id,
                'method' => $validated['payment_method'],
                'amount' => $newTotal
            ]);

            // Create new items and update stock
            foreach ($validated['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price']
                ]);

                // Update stock
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->current_stock = max(0, $product->current_stock - $item['quantity']);
                    $product->save();
                }
            }

            return redirect()->route('sales.show', $sale->id)->with('success', 'Sale updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update sale: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        
        // Restore stock for each item
        foreach ($sale->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->current_stock += $item->quantity;
                $product->save();
            }
        }
        
        // Delete sale items first
        $sale->items()->delete();
        
        // Delete associated payments
        $sale->payments()->delete();
        
        // Delete sale
        $sale->delete();
        
        // Check if this is an AJAX request
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sale deleted successfully. Stock has been restored and payments removed.'
            ]);
        }
        
        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully');
    }
}
