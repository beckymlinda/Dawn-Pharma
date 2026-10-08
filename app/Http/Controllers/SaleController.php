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
use App\Models\IntegrityEvent;
use App\Services\IntegrityMonitor;
use App\Services\SaleEditor;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        // Start with base query
        $query = Sale::with('register.user', 'items.product', 'payments');
        
        // Data isolation: Cashiers only see their own sales
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            $query->whereHas('register', function($q) {
                $q->where('user_id', auth()->id());
            });
        }
        
        $period = $request->input('period', 'today');
        
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
            $query->where(function($q) use ($request) {
                $q->where('payment_method', $request->payment_method)
                  ->orWhere(function($subQ) use ($request) {
                      $subQ->where('payment_method', 'split')
                           ->whereHas('payments', function($pq) use ($request) {
                               $pq->where('method', $request->payment_method);
                           });
                  });
            });
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
    
    private function calculateSalesSummary($sales, $request, $period = 'today')
    {
        $summary = [];
        
        // Start with base query for sales totals
        $query = \App\Models\Sale::query();
        
        // Data isolation: Cashiers only see their own sales totals
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            $query->whereHas('register', function($q) {
                $q->where('user_id', auth()->id());
            });
        }
        
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
                $count = 0;
                $total = 0;
                foreach ($filteredSales as $sale) {
                    if ($sale->payment_method === $method) {
                        $count++;
                        $total += $sale->total_amount;
                    } elseif ($sale->payment_method === 'split') {
                        $payment = $sale->payments->where('method', $method)->first();
                        if ($payment) {
                            $count++;
                            $total += $payment->amount;
                        }
                    }
                }
                $paymentSummary[$method] = [
                    'count' => $count,
                    'total' => $total
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

        if (!$sale->register_id) {
            IntegrityEvent::record('sale_without_register', 'medium',
                "Sale {$sale->invoice_no} of " . formatCurrency($sale->total_amount) . ' recorded outside the POS, without an open register',
                ['sale_id' => $sale->id, 'invoice_no' => $sale->invoice_no, 'amount' => $sale->total_amount]);
        }
        IntegrityMonitor::checkCompletedSale($sale);

        return redirect()->route('sales.show', $sale->id);
    }

    public function show($id)
    {
        $sale = Sale::with('items.product')->findOrFail($id);
        return view('pos.sale-show', compact('sale'));
    }

    public function edit($id)
    {
        $sale = Sale::with(['items.product', 'payments', 'register.user'])->findOrFail($id);

        if ($denied = SaleEditor::denialReason($sale, auth()->user())) {
            return redirect()->route('sales.show', $sale->id)->with('error', $denied);
        }

        $isSupervisor = SaleEditor::isSupervisor(auth()->user());
        $lockedPrices = SaleEditor::lockedPrices($sale);
        // Stock that can go on this sale = what's on the shelf + what this sale already holds
        $originalQty = $sale->items->groupBy('product_id')->map->sum('quantity');

        // Everything the page script needs, as plain data
        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'selling_price', 'current_stock'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) ($isSupervisor ? $p->selling_price : ($lockedPrices[$p->id] ?? $p->selling_price)),
                'list_price' => (float) $p->selling_price,
                'available' => (float) $p->current_stock + (float) ($originalQty[$p->id] ?? 0),
            ])->values();

        $original = $sale->items->groupBy('product_id')->map(fn ($rows, $pid) => [
            'product_id' => (int) $pid,
            'quantity' => (float) $rows->sum('quantity'),
            'price' => (float) $rows->first()->price,
        ])->values();

        // After a failed save, show what the user had entered rather than the stored sale
        $items = old('items')
            ? collect(old('items'))->map(fn ($i) => ['product_id' => (int) $i['product_id'], 'quantity' => (float) $i['quantity'], 'price' => (float) ($i['price'] ?? 0)])->values()
            : $original;
        $payments = old('payments')
            ? collect(old('payments'))->map(fn ($p) => ['method' => $p['method'], 'amount' => (float) $p['amount']])->values()
            : ($sale->payments->isNotEmpty()
                ? $sale->payments->map(fn ($p) => ['method' => $p->method, 'amount' => (float) $p->amount])->values()
                : collect([['method' => $sale->payment_method === 'split' ? 'cash' : $sale->payment_method, 'amount' => (float) $sale->total_amount]]));

        // Methods that are on, plus whatever this sale was already paid with (even if since switched off)
        $methods = PaymentMethods::enabledNames();
        foreach ($payments as $p) {
            $methods += PaymentMethods::enabledNames($p['method']);
        }

        return view('pos.sale-edit', compact('sale', 'products', 'original', 'items', 'payments', 'methods', 'isSupervisor'));
    }

    public function update(Request $request, $id, SaleEditor $editor)
    {
        $sale = Sale::findOrFail($id);

        $validated = $request->validate([
            'edit_reason' => 'required|string|min:5|max:255',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
        ], [
            'edit_reason.required' => 'Give a reason for changing this sale.',
            'edit_reason.min' => 'The reason must be at least 5 characters.',
        ]);

        $editor->update($sale, $validated['items'], $validated['payments'], trim($validated['edit_reason']), 'Sales page');

        return redirect()->route('sales.show', $sale->id)->with('success', 'Sale updated. Stock, payments and reports now reflect the change.');
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);

        if (!auth()->user()->hasPermission('delete_pos_sell')) {
            IntegrityEvent::record('delete_blocked', 'high',
                "Tried to delete sale {$sale->invoice_no} (" . formatCurrency($sale->total_amount) . ') without permission',
                ['sale_id' => $sale->id, 'invoice_no' => $sale->invoice_no, 'amount' => $sale->total_amount, 'details' => IntegrityMonitor::snapshot($sale)]);

            $message = 'You do not have permission to delete sales. Ask a manager.';
            return request()->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 403)
                : back()->with('error', $message);
        }

        // Keep a permanent copy before the sale disappears
        IntegrityEvent::record('sale_deleted', 'critical',
            "Deleted sale {$sale->invoice_no} of " . formatCurrency($sale->total_amount) . " ({$sale->payment_method}) made "
                . optional($sale->created_at)->format('d M Y H:i'),
            [
                'sale_id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'register_id' => $sale->register_id,
                'amount' => $sale->total_amount,
                'details' => IntegrityMonitor::snapshot($sale) + ['sold_by' => $sale->user?->name ?? $sale->register?->user?->name],
            ]);

        // Restore stock for each item
        foreach ($sale->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->current_stock += $item->quantity;
                $product->save();

                // Keep the stock history complete (reports rebuild past stock levels from it)
                \App\Models\StockHistory::create([
                    'product_id' => $product->id,
                    'type' => 'Stock Adjustment',
                    'quantity_change' => $item->quantity,
                    'new_quantity' => $product->current_stock,
                    'unit_cost' => $item->purchase_cost ?? $product->purchase_price,
                    'unit_price' => $item->price,
                    'date' => now()->toDateString(),
                    'reference_no' => $sale->invoice_no,
                    'notes' => 'Stock returned: sale #' . $sale->invoice_no . ' deleted by ' . auth()->user()->name,
                ]);
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
