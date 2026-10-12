<?php

namespace App\Http\Controllers;

use App\Models\POS;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use App\Models\Payment;
use App\Models\StockHistory;
use App\Helpers\PaymentMethods;
use App\Models\IntegrityEvent;
use App\Models\Register;
use App\Services\IntegrityMonitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function index()
    {
        // Redirect to new POS as main POS
        return redirect()->route('pos.new');
    }

    public function create()
    {
        // Check if user has open register
        $openRegister = RegisterController::getOpenRegister();
        
        if (!$openRegister) {
            return redirect()->route('register.create')
                ->with('error', 'Please open a register first');
        }

        $products = $this->posProducts();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('pos.pos', compact('products', 'categories', 'brands', 'openRegister'));
    }

    public function newIndex()
    {
        // Check if user has open register
        $openRegister = RegisterController::getOpenRegister();
        
        if (!$openRegister) {
            return redirect()->route('register.create')
                ->with('error', 'Please open a register first');
        }

        // Clear any existing cart to ensure clean start for new sale
        session()->forget('pos_cart');
        
        $products = $this->posProducts();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('pos.pos', compact('products', 'categories', 'brands', 'openRegister'));
    }

    public function registerDetails()
    {
        // Get current register details
        $register = RegisterController::getOpenRegister();
        
        if (!$register) {
            return redirect()->route('pos.new')
                ->with('error', 'No open register found');
        }

        // Get register sales data directly (not the JSON response)
        $registerData = $this->getRegisterDetailsData();
        
        return view('pos.register_details', compact('register', 'registerData'));
    }

    public function showRegister($id)
    {
        $register = \App\Models\Register::findOrFail($id);
        $registerData = $this->getRegisterDetailsData($register);
        return view('pos.register_details', compact('register', 'registerData'));
    }

    private function getRegisterDetailsData($register = null)
    {
        // Check if user has open register
        if (!$register) {
            $register = RegisterController::getOpenRegister();
        }
        
        if (!$register) {
            return [
                'success' => false,
                'error' => 'No register found.'
            ];
        }

        try {
            // Get all sales for this register with their items and products
            $sales = Sale::where('register_id', $register->id)
                ->with('items.product', 'items.product.brand')
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate payment method totals using shared helper
            $paymentMethods = PaymentMethods::calculateTotals($sales);

            $totalSales = 0;
            $totalRefund = 0;
            $totalPayment = 0;
            $creditSales = 0;
            $totalExpense = 0;

            foreach ($sales as $sale) {
                $totalSales += $sale->total_amount;
                $totalPayment += $sale->total_paid;
            }

            // Process products and brands
            $products = [];
            $brands = [];
            $totalQuantity = 0;

            foreach ($sales as $sale) {
                foreach ($sale->items as $item) {
                    $sku = $item->product->sku ?? '';
                    $name = $item->product->name ?? 'Unknown Product';
                    $quantity = $item->quantity;
                    $totalAmount = $item->price * $quantity; // Calculate total from price and quantity
                    $brand = $item->product->brand->name ?? 'Unknown Brand';

                    // Aggregate products
                    if (isset($products[$sku])) {
                        $products[$sku]['quantity'] += $quantity;
                        $products[$sku]['total_amount'] += $totalAmount;
                    } else {
                        $products[$sku] = [
                            'sku' => $sku,
                            'name' => $name,
                            'quantity' => $quantity,
                            'total_amount' => $totalAmount
                        ];
                    }

                    // Aggregate brands
                    if (isset($brands[$brand])) {
                        $brands[$brand]['quantity'] += $quantity;
                        $brands[$brand]['total_amount'] += $totalAmount;
                    } else {
                        $brands[$brand] = [
                            'brand' => $brand,
                            'quantity' => $quantity,
                            'total_amount' => $totalAmount
                        ];
                    }

                    $totalQuantity += $quantity;
                }
            }

            // Convert to arrays
            $products = array_values($products);
            $brands = array_values($brands);

            // Calculate cash in hand (total from all payment methods)
            $cashInHand = array_sum($paymentMethods);

            return [
                'success' => true,
                'register' => [
                    'id' => $register->id,
                    'opened_at' => $register->opened_at,
                    'opening_cash' => $register->opening_cash,
                    'user' => [
                        'name' => $register->user->name ?? 'Unknown',
                        'email' => $register->user->email ?? ''
                    ],
                    'business_location' => $register->business_location ?? 'N/A',
                    'payment_methods' => $paymentMethods,
                    'totals' => [
                        'total_sales' => $totalSales,
                        'total_refund' => $totalRefund,
                        'total_payment' => $totalPayment,
                        'credit_sales' => $creditSales,
                        'total_expense' => $totalExpense,
                        'cash_in_hand' => $cashInHand // Total from all payment methods
                    ],
                    'products' => $products,
                    'brands' => $brands,
                    'total_quantity' => $totalQuantity
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to load register details: ' . $e->getMessage()
            ];
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_no' => 'required|string',
            'date' => 'nullable|date',
            'customer_name' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'total_amount' => 'required|numeric',
            'total_paid' => 'required|numeric',
            'return_due' => 'nullable|numeric',
            'shipping_status' => 'nullable|string',
            'total_items' => 'required|integer'
        ]);

        POS::create($data);

        return redirect()->route('pos.index');
    }

    // Simple session-backed cart handlers
    public function addToCart(Request $request)
    {
        // Debug logging
        \Log::info('addToCart called', ['request_data' => $request->all()]);
        
        try {
            $data = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|numeric|min:0.01',
                'clear_cart' => 'sometimes|boolean'
            ]);
            \Log::info('Validation passed', ['validated_data' => $data]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . implode(', ', \Illuminate\Support\Arr::flatten($e->errors()))
            ], 400);
        }

        // Failsafe: Clear cart if explicitly requested or if starting new sale
        if (isset($data['clear_cart']) && $data['clear_cart']) {
            session()->forget('pos_cart');
            \Log::info('Cart cleared due to clear_cart flag');
        }

        $product = Product::find($data['product_id']);
        
        // Check if product exists and has enough stock
        if (!$product) {
            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }
        
        // Check if stock is negative or invalid
        if ($product->current_stock < 0) {
            return response()->json([
                'success' => false,
                'error' => 'Product has invalid stock level (' . $product->current_stock . '). Please contact administrator.'
            ], 400);
        }

        // Prevent sale when stock is zero
        if ($product->current_stock == 0) {
            return response()->json([
                'success' => false,
                'error' => 'Item out of stock'
            ], 400);
        }

        if ($product->current_stock < $data['quantity']) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient stock. Available: ' . $product->current_stock . ', Requested: ' . $data['quantity']
            ], 400);
        }

        // Force clear cart if requested
        if (isset($data['clear_cart']) && $data['clear_cart']) {
            session()->forget('pos_cart');
            $cart = [];
        } else {
            $cart = session()->get('pos_cart', []);
        }

        // Check if product already exists in cart
        $existingIndex = null;
        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $product->id) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            // Update existing item quantity
            $newQuantity = $cart[$existingIndex]['quantity'] + $data['quantity'];
            
            // Block if requested total exceeds available stock
            if ($product->current_stock > 0 && $product->current_stock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient stock. Available: ' . $product->current_stock . ', Requested total: ' . $newQuantity
                ], 400);
            }

            $cart[$existingIndex]['quantity'] = $newQuantity;
            $cart[$existingIndex]['subtotal'] = $cart[$existingIndex]['price'] * $newQuantity;
            if (!isset($cart[$existingIndex]['purchase_price'])) {
                $cart[$existingIndex]['purchase_price'] = $product->purchase_price;
            }
        } else {
            // Add new item to cart at the end
            $newItem = [
                'product_id' => $product->id,
                'sku' => $product->sku ?? '',
                'name' => $product->name,
                'quantity' => $data['quantity'],
                'price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'subtotal' => $data['quantity'] * $product->selling_price
            ];
            
            // Always add new item to the end of cart
            array_push($cart, $newItem);
        }

        session()->put('pos_cart', $cart);

        return response()->json(['cart' => $cart, 'success' => true]);
    }

    public function removeFromCart(Request $request)
    {
        $productId = $request->input('product_id');
        $cart = session()->get('pos_cart', []);
        
        // Find item by product_id in cart
        $itemIndex = null;
        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $productId) {
                $itemIndex = $index;
                break;
            }
        }
        
        if ($itemIndex !== null) {
            array_splice($cart, $itemIndex, 1);
            session()->put('pos_cart', $cart);
        }
        
        $totalItems = collect($cart)->sum('quantity');
        $totalAmount = collect($cart)->sum('subtotal');
        return response()->json(['cart' => $cart, 'totalItems' => $totalItems, 'totalAmount' => $totalAmount, 'success' => true]);
    }

    public function getCart(Request $request)
    {
        $cart = session()->get('pos_cart', []);
        foreach ($cart as $index => $item) {
            if (!isset($item['purchase_price']) && isset($item['product_id'])) {
                $product = Product::find($item['product_id']);
                $cart[$index]['purchase_price'] = $product ? $product->purchase_price : 0;
            }
        }
        session()->put('pos_cart', $cart);

        $totalItems = collect($cart)->sum('quantity');
        $totalAmount = collect($cart)->sum('subtotal');

        return response()->json([
            'cart' => $cart,
            'totalItems' => $totalItems,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function updateCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $cart = session()->get('pos_cart', []);
        
        // Find item by product_id in cart
        $itemIndex = null;
        foreach ($cart as $index => $item) {
            if ($item['product_id'] == $data['product_id']) {
                $itemIndex = $index;
                break;
            }
        }
        
        if ($itemIndex === null) {
            return response()->json(['success' => false, 'error' => 'Item not found in cart'], 404);
        }

        $product = Product::find($data['product_id']);
        
        if (!$product) {
            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }
        
        // Check if stock is negative (invalid)
        if ($product->current_stock < 0) {
            return response()->json([
                'success' => false,
                'error' => 'Product has invalid stock level (' . $product->current_stock . '). Please contact administrator.'
            ], 400);
        }

        if ($product->current_stock == 0) {
            return response()->json([
                'success' => false,
                'error' => 'Item out of stock'
            ], 400);
        }

        if ($product->current_stock < $data['quantity']) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient stock. Available: ' . $product->current_stock . ', Requested: ' . $data['quantity']
            ], 400);
        }

        // Update the quantity and recalculate subtotal
        $cart[$itemIndex]['quantity'] = $data['quantity'];
        $cart[$itemIndex]['subtotal'] = $cart[$itemIndex]['price'] * $data['quantity'];
        
        session()->put('pos_cart', $cart);

        foreach ($cart as $index => $item) {
            if (!isset($item['purchase_price']) && isset($item['product_id'])) {
                $product = Product::find($item['product_id']);
                $cart[$index]['purchase_price'] = $product ? $product->purchase_price : 0;
            }
        }

        return response()->json(['cart' => $cart, 'success' => true]);
    }

    public function clearCart(Request $request)
    {
        // Clear the cart from session
        session()->forget('pos_cart');
        
        return response()->json([
            'success' => true,
            'cart' => [],
            'message' => 'Cart cleared successfully'
        ]);
    }

    public function finalizeSale(Request $request)
    {
        // Sales made while the till was offline are uploaded later (public/js/pos/offline.js).
        // The goods have already left the shop, so such a sale is recorded at the time it
        // was made and is never rejected for stock or price; problems are flagged instead.
        $offline = $request->boolean('offline');
        $soldAt = $offline ? $this->offlineSaleTime($request->input('sold_at')) : null;

        // The invoice number is generated once per sale on the till, so a repeated request
        // (a retry after a dropped connection, or an offline sale synced twice) returns
        // the sale that was already saved instead of failing or creating a duplicate.
        $existing = $request->filled('invoice_no')
            ? Sale::where('invoice_no', $request->input('invoice_no'))->first()
            : null;
        if ($existing) {
            if ((int) $existing->user_id !== (int) auth()->id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invoice number ' . $existing->invoice_no . ' is already used by another sale.'
                ], 409);
            }

            return response()->json([
                'success' => true,
                'duplicate' => true,
                'sale_id' => $existing->id,
                'invoice_no' => $existing->invoice_no,
                'message' => 'Sale was already saved',
                'clear_cart' => true,
            ]);
        }

        // Check if user has open register (for an offline sale: the register open when it was made)
        $openRegister = $offline
            ? $this->registerForOfflineSale($soldAt)
            : RegisterController::getOpenRegister();

        if (!$openRegister) {
            return response()->json([
                'success' => false,
                'error' => 'No open register. Please open a register first.'
            ], 400);
        }

        $cart = $request->input('items', []);
        if (empty($cart)) {
            $cart = session()->get('pos_cart', []);
        }
        
        if (empty($cart)) {
            return response()->json([
                'success' => false, 
                'error' => 'Cart is empty. Please add items to cart first.'
            ], 400);
        }

        // Log debug info
        \Log::info('Finalize sale attempt', [
            'cart_items' => count($cart),
            'request_data' => $request->all()
        ]);

        try {
            // Start database transaction
            DB::beginTransaction();
            
            // Lock the products being sold until this sale commits, so two tills
            // selling the last unit at the same moment cannot both succeed.
            // Locked in id order so concurrent sales never deadlock each other.
            $productIds = collect($cart)->pluck('product_id')->filter()->unique()->sort()->values();
            $products = Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $requestedQty = [];
            foreach ($cart as $key => $item) {
                $product = $products->get($item['product_id'] ?? null);
                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => 'Product not found in cart: ' . ($item['product_id'] ?? 'unknown')
                    ], 400);
                }

                $quantity = $item['quantity'] ?? null;
                if (!is_numeric($quantity) || $quantity <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid quantity for product: ' . $product->name
                    ], 400);
                }

                // Price always comes from the product record, never from the browser,
                // so a sale cannot be recorded below the catalogue price.
                $cart[$key]['quantity'] = (float) $quantity;
                $cart[$key]['price'] = (float) $product->selling_price;
                $cart[$key]['subtotal'] = round($cart[$key]['price'] * $cart[$key]['quantity'], 2);

                $requestedQty[$product->id] = ($requestedQty[$product->id] ?? 0) + $cart[$key]['quantity'];
            }

            // Check stock against the total requested per product (the same product may appear on several lines)
            $stockShortfalls = [];
            foreach ($requestedQty as $productId => $quantity) {
                $product = $products[$productId];

                if ($offline) {
                    // Already sold: record it, and flag stock that did not add up
                    if ($product->current_stock < $quantity) {
                        $stockShortfalls[] = ['product' => $product, 'sold' => $quantity, 'in_stock' => (float) $product->current_stock];
                    }
                    continue;
                }

                if ($product->current_stock < 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => 'Product ' . $product->name . ' has invalid stock level (' . $product->current_stock . '). Please contact administrator.'
                    ], 400);
                }

                if ($product->current_stock < $quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => 'Insufficient stock for product: ' . $product->name .
                                 '. Available: ' . $product->current_stock .
                                 ', Requested: ' . $quantity
                    ], 400);
                }
            }
            
            // Check if this is a split payment (now handled as separate sales)
            $isSplitPayment = $request->input('is_split_payment', false);
            $paymentsData = $request->input('payments', []);
            $singlePaymentMethod = $request->input('payment_method');

            // Determine if we should handle as split payments
            $handleAsSplitPayment = $isSplitPayment && !empty($paymentsData) && count($paymentsData) > 1;

            if ($handleAsSplitPayment) {
                // Handle multiple payment methods - CREATE SEPARATE SALES
                $totalAmount = collect($cart)->sum('subtotal');
                $totalItems = collect($cart)->sum('quantity');
                
                $validated = $request->validate([
                    'invoice_no' => 'required|string',
                    'customer_name' => 'nullable|string',
                    'contact_number' => 'nullable|string',
                    'payment_status' => 'required|string',
                    'payments' => 'required|array|min:2',
                    'payments.*.method' => 'required|string',
                    'payments.*.amount' => 'required|numeric|min:0.01'
                ]);

                // The payments must cover the whole sale, otherwise goods leave unpaid for
                // (an offline sale was already paid at the till; it is flagged instead)
                if (!$offline && round(collect($paymentsData)->sum('amount'), 2) < round($totalAmount, 2)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => 'Payments (' . number_format(collect($paymentsData)->sum('amount'), 2) .
                                 ') do not cover the sale total (' . number_format($totalAmount, 2) . ').'
                    ], 400);
                }

                $createdSales = [];
                $baseInvoiceNo = $validated['invoice_no'];
                
                // Create single sale record for the split payment
                $sale = Sale::create([
                    'register_id' => $openRegister->id,
                    'invoice_no' => $baseInvoiceNo,
                    'customer_name' => $validated['customer_name'] ?? 'Walk-In Customer',
                    'contact_number' => $validated['contact_number'] ?? null,
                    'location' => 'POS Terminal',
                    'payment_status' => $validated['payment_status'],
                    'payment_method' => 'split',
                    'total_amount' => $totalAmount,
                    'total_paid' => collect($paymentsData)->sum('amount'),
                    'sell_due' => 0,
                    'total_items' => $totalItems,
                ]);
                if ($soldAt) {
                    $sale->forceFill(['created_at' => $soldAt])->saveQuietly();
                }

                // Create POS record for tracking
                POS::create([
                    'date' => $soldAt ?? now(),
                    'invoice_no' => $baseInvoiceNo,
                    'customer_name' => $validated['customer_name'] ?? 'Walk-In Customer',
                    'payment_status' => $validated['payment_status'],
                    'payment_method' => 'split',
                    'total_amount' => $totalAmount,
                    'total_paid' => collect($paymentsData)->sum('amount'),
                    'return_due' => 0,
                    'shipping_status' => 'Completed',
                    'total_items' => $totalItems,
                ]);

                // Create multiple payment records
                foreach ($paymentsData as $paymentData) {
                    Payment::create([
                        'sale_id' => $sale->id,
                        'method' => $paymentData['method'],
                        'amount' => $paymentData['amount'],
                    ]);
                }

                // Create sale items and decrement stock once
                foreach ($cart as $item) {
                    $product = $products[$item['product_id']];
                    $purchaseCost = $product ? $product->purchase_price : 0;
                    
                    if (!$product || (!$offline && $product->current_stock < $item['quantity'])) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'error' => 'Insufficient stock for product: ' . ($product->name ?? 'Unknown') . 
                                     '. Available: ' . ($product ? $product->current_stock : 0) . 
                                     ', Requested: ' . $item['quantity']
                        ], 400);
                    }
                    
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['subtotal'],
                        'purchase_cost' => $purchaseCost
                    ]);

                    $product->current_stock = max(0, $product->current_stock - $item['quantity']);
                    $product->save();
                    
                    StockHistory::create([
                        'product_id' => $product->id,
                        'type' => 'Sale',
                        'quantity_change' => -$item['quantity'],
                        'new_quantity' => $product->current_stock,
                        'unit_cost' => $purchaseCost,
                        'unit_price' => $item['price'],
                        'date' => ($soldAt ?? now())->toDateString(),
                        'reference_no' => $baseInvoiceNo,
                        'customer_supplier' => $validated['customer_name'] ?? 'Walk-In Customer',
                        'notes' => 'Sold in invoice #' . $baseInvoiceNo . ' (split)'
                    ]);
                }
                
                $createdSales[] = $sale;

                // Clear cart after successful multiple payments
                session()->forget('pos_cart');

                // Commit database transaction
                DB::commit();

                IntegrityMonitor::checkCompletedSale($sale);
                if ($offline) {
                    $this->flagOfflineSale($sale, $openRegister, $stockShortfalls, $request->input('paid_total'));
                }

                return response()->json([
                    'success' => true,
                    'sales' => $createdSales,
                    'payments' => $paymentsData,
                    'total_amount' => $totalAmount,
                    'total_items' => $totalItems,
                    'message' => 'Sales created successfully for each payment method',
                    'clear_cart' => true
                ]);

            } else {
                // Handle single payment (original logic)
                $totalAmount = collect($cart)->sum('subtotal');
                $totalItems = collect($cart)->sum('quantity');

                // Validate required fields for single payment
                $validated = $request->validate([
                    'invoice_no' => 'required|string',
                    'customer_name' => 'nullable|string',
                    'contact_number' => 'nullable|string',
                    'payment_method' => 'required|string',
                    'payment_status' => 'required|string',
                ]);

                $sale = Sale::create([
                    'register_id' => $openRegister->id,
                    'invoice_no' => $validated['invoice_no'],
                    'customer_name' => $validated['customer_name'] ?? 'Walk-In Customer',
                    'contact_number' => $validated['contact_number'] ?? null,
                    'location' => 'POS Terminal',
                    'payment_status' => $validated['payment_status'],
                    'payment_method' => $validated['payment_method'],
                    'total_amount' => $totalAmount,
                    'total_paid' => $totalAmount,
                    'sell_due' => 0,
                    'total_items' => $totalItems,
                ]);
                if ($soldAt) {
                    $sale->forceFill(['created_at' => $soldAt])->saveQuietly();
                }

                // Create POS record for tracking
                POS::create([
                    'date' => $soldAt ?? now(),
                    'invoice_no' => $validated['invoice_no'],
                    'customer_name' => $validated['customer_name'] ?? 'Walk-In Customer',
                    'payment_status' => $validated['payment_status'],
                    'payment_method' => $validated['payment_method'],
                    'total_amount' => $totalAmount,
                    'total_paid' => $totalAmount,
                    'return_due' => 0,
                    'shipping_status' => 'Completed',
                    'total_items' => $totalItems,
                ]);

                // Create payment record
                Payment::create([
                    'sale_id' => $sale->id,
                    'method' => $validated['payment_method'],
                    'amount' => $totalAmount,
                ]);

                // Create sale items and decrement stock
                foreach ($cart as $item) {
                    $product = $products[$item['product_id']];
                    $purchaseCost = $product ? $product->purchase_price : 0;
                    
                    // Final stock validation before creating sale item
                    if (!$product || (!$offline && $product->current_stock < $item['quantity'])) {
                        // Rollback database transaction
                        DB::rollBack();
                        
                        return response()->json([
                            'success' => false,
                            'error' => 'Insufficient stock for product: ' . ($product->name ?? 'Unknown') . 
                                     '. Available: ' . ($product ? $product->current_stock : 0) . 
                                     ', Requested: ' . $item['quantity']
                        ], 400);
                    }
                    
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['subtotal'],
                        'purchase_cost' => $purchaseCost // Capture purchase cost at time of sale
                    ]);

                    // Decrement stock and create stock history
                    $oldStock = $product->current_stock;
                    $product->current_stock = max(0, $product->current_stock - $item['quantity']);
                    $product->save();
                    
                    // Debug logging
                    \Log::info('Stock deduction for product: ' . $product->name);
                    \Log::info('Old stock: ' . $oldStock);
                    \Log::info('Quantity deducted: ' . $item['quantity']);
                    \Log::info('New stock: ' . $product->current_stock);
                    
                    // Create stock history record
                    StockHistory::create([
                        'product_id' => $product->id,
                        'type' => 'Sale',
                        'quantity_change' => -$item['quantity'], // Negative for outbound
                        'new_quantity' => $product->current_stock,
                        'unit_cost' => $purchaseCost,
                        'unit_price' => $item['price'],
                        'date' => ($soldAt ?? now())->toDateString(),
                        'reference_no' => $validated['invoice_no'],
                        'customer_supplier' => $validated['customer_name'] ?? 'Walk-In Customer',
                        'notes' => 'Sold in invoice #' . $validated['invoice_no'] . ' (' . $validated['payment_method'] . ')'
                    ]);
                }

                // Clear cart after successful single payment
                session()->forget('pos_cart');
                \Log::info('Cart cleared after successful sale', ['invoice_no' => $sale->invoice_no]);

                // Commit database transaction
                DB::commit();

                IntegrityMonitor::checkCompletedSale($sale);
                if ($offline) {
                    $this->flagOfflineSale($sale, $openRegister, $stockShortfalls, $request->input('paid_total'));
                }

                return response()->json([
                    'success' => true,
                    'sale_id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'message' => 'Sale finalized successfully',
                    'clear_cart' => true,
                    'redirect' => route('sales.show', $sale->id)
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . implode(', ', \Illuminate\Support\Arr::flatten($e->errors()))
            ], 400);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error('Database error during finalize sale', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('General error during finalize sale', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to finalize sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * When an offline sale was made, as sent by the till. Times in the future or
     * more than a week ago are not trusted (wrong device clock) and become "now".
     */
    private function offlineSaleTime($soldAt): Carbon
    {
        try {
            $time = Carbon::parse($soldAt)->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            return now();
        }

        return $time->isFuture() || $time->lt(now()->subDays(7)) ? now() : $time;
    }

    /**
     * The register an offline sale belongs to: the cashier's register that was open
     * when the sale was made, otherwise their currently open one.
     */
    private function registerForOfflineSale(Carbon $soldAt): ?Register
    {
        return Register::where('user_id', auth()->id())
                ->where('opened_at', '<=', $soldAt)
                ->where(fn ($q) => $q->whereNull('closed_at')->orWhere('closed_at', '>=', $soldAt))
                ->latest('opened_at')
                ->first()
            ?? RegisterController::getOpenRegister();
    }

    /**
     * Raise loss-prevention events for an uploaded offline sale whose stock or
     * payment did not add up, or that arrived after its register was closed.
     */
    private function flagOfflineSale(Sale $sale, Register $register, array $stockShortfalls, $paidTotal): void
    {
        $base = ['sale_id' => $sale->id, 'invoice_no' => $sale->invoice_no, 'register_id' => $register->id];

        foreach ($stockShortfalls as $shortfall) {
            $product = $shortfall['product'];
            $missing = $shortfall['sold'] - max(0, $shortfall['in_stock']);
            IntegrityEvent::record('offline_stock_shortfall', 'medium',
                "Offline sale {$sale->invoice_no} sold {$shortfall['sold']} x {$product->name} but only {$shortfall['in_stock']} were in stock",
                $base + ['product_id' => $product->id, 'amount' => $missing * (float) $product->purchase_price,
                         'details' => ['sold' => $shortfall['sold'], 'in_stock' => $shortfall['in_stock']]]);
        }

        if (is_numeric($paidTotal) && round((float) $paidTotal, 2) < round((float) $sale->total_amount, 2)) {
            IntegrityEvent::record('offline_underpaid', 'medium',
                "Offline sale {$sale->invoice_no} was paid " . number_format($paidTotal, 2) . ' against a total of ' . number_format($sale->total_amount, 2),
                $base + ['amount' => $sale->total_amount - $paidTotal,
                         'details' => ['paid' => (float) $paidTotal, 'total' => (float) $sale->total_amount]]);
        }

        if ($register->closed_at) {
            IntegrityEvent::record('offline_late_sync', 'low',
                "Offline sale {$sale->invoice_no} was uploaded after register #{$register->id} was closed",
                $base + ['amount' => $sale->total_amount, 'details' => ['register_closed_at' => (string) $register->closed_at]]);
        }
    }

    /**
     * Products for the POS screen. Cost prices are left out unless the user may
     * see them, because everything sent to the till can be read in the browser.
     */
    private function posProducts()
    {
        $products = Product::with('brand', 'category')->orderBy('name')->get();

        if (!auth()->user()->hasPermission('view_purchase_price')) {
            $products->each->makeHidden(['purchase_price', 'last_purchase_price']);
        }

        return $products;
    }

    public function refreshProducts()
    {
        try {
            // Get fresh product data with current stock levels
            $products = $this->posProducts();
            
            return response()->json([
                'success' => true,
                'products' => $products,
                'stock_at' => now()->getTimestampMs(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh products: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRecentSales()
    {
        // Check if user has open register
        $openRegister = RegisterController::getOpenRegister();
        
        if (!$openRegister) {
            return response()->json([
                'success' => false,
                'error' => 'No open register. Please open a register first.'
            ], 400);
        }

        try {
            // Get recent sales for current register (last 50 sales)
            $recentSales = Sale::where('register_id', $openRegister->id)
                ->with('items.product', 'payments')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'sales' => $recentSales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent sales: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRegisterDetails()
    {
        // Check if user has open register
        $openRegister = RegisterController::getOpenRegister();
        
        if (!$openRegister) {
            return response()->json([
                'success' => false,
                'error' => 'No open register. Please open a register first.'
            ], 400);
        }

        try {
            // Get all sales for this register with their items and products
            $sales = Sale::where('register_id', $openRegister->id)
                ->with('items.product', 'items.product.brand')
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate payment method totals (every method of this shop, including ones it added)
            $paymentMethods = PaymentMethods::initializeTotals();

            $totalSales = 0;
            $totalRefund = 0;
            $totalPayment = 0;
            $creditSales = 0;
            $totalExpense = 0;

            foreach ($sales as $sale) {
                if ($sale->payment_method === 'split' && $sale->payments) {
                    foreach ($sale->payments as $payment) {
                        $method = strtolower($payment->method);
                        if (isset($paymentMethods[$method])) {
                            $paymentMethods[$method] += $payment->amount;
                        } else {
                            $paymentMethods['other'] += $payment->amount;
                        }
                    }
                } else {
                    $method = strtolower($sale->payment_method);
                    
                    if (isset($paymentMethods[$method])) {
                        $paymentMethods[$method] += $sale->total_amount;
                    } else {
                        $paymentMethods['other'] += $sale->total_amount;
                    }
                }
                
                $totalSales += $sale->total_amount;
                
                if ($sale->payment_status === 'refunded') {
                    $totalRefund += $sale->total_amount;
                }
                
                if ($sale->payment_status === 'paid') {
                    $totalPayment += $sale->total_paid;
                }
                
                if ($sale->payment_status === 'credit') {
                    $creditSales += $sale->total_amount;
                }
            }

            // Get product details
            $productDetails = [];
            $brandDetails = [];
            $totalQuantity = 0;

            foreach ($sales as $sale) {
                foreach ($sale->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        // Product details
                        $sku = $product->sku ?? 'N/A';
                        $productName = $product->name ?? 'Unknown Product';
                        $quantity = $item->quantity;
                        $totalAmount = $item->subtotal;

                        if (!isset($productDetails[$sku])) {
                            $productDetails[$sku] = [
                                'sku' => $sku,
                                'name' => $productName,
                                'quantity' => 0,
                                'total_amount' => 0
                            ];
                        }

                        $productDetails[$sku]['quantity'] += $quantity;
                        $productDetails[$sku]['total_amount'] += $totalAmount;
                        $totalQuantity += $quantity;

                        // Brand details
                        $brandName = $product->brand ? $product->brand->name : 'No Brand';
                        
                        if (!isset($brandDetails[$brandName])) {
                            $brandDetails[$brandName] = [
                                'brand' => $brandName,
                                'quantity' => 0,
                                'total_amount' => 0
                            ];
                        }

                        $brandDetails[$brandName]['quantity'] += $quantity;
                        $brandDetails[$brandName]['total_amount'] += $totalAmount;
                    }
                }
            }

            // Get user info
            $user = auth()->user();

            return response()->json([
                'success' => true,
                'register' => [
                    'id' => $openRegister->id,
                    'opened_at' => $openRegister->opened_at,
                    'opening_cash' => $openRegister->opening_cash,
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'business_location' => setting('system_name'),
                    'payment_methods' => $paymentMethods,
                    'totals' => [
                        'total_sales' => $totalSales,
                        'total_refund' => $totalRefund,
                        'total_payment' => $totalPayment,
                        'credit_sales' => $creditSales,
                        'total_expense' => $totalExpense,
                        'cash_in_hand' => $openRegister->opening_cash
                    ],
                    'products' => array_values($productDetails),
                    'brands' => array_values($brandDetails),
                    'total_quantity' => $totalQuantity
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load register details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSaleEditData($saleId)
    {
        try {
            $sale = Sale::with('items.product')->findOrFail($saleId);
            
            // Verify user has access to this sale (same register or admin)
            $openRegister = RegisterController::getOpenRegister();
            if (!$openRegister || $sale->register_id !== $openRegister->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'You can only edit sales from your current register session.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'sale' => $sale
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Not a sale of this shop
            return response()->json(['success' => false, 'error' => 'Sale not found.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load sale data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editSale(Request $request, \App\Services\SaleEditor $editor)
    {
        $validated = $request->validate([
            'original_sale_id' => 'required|exists:sales,id',
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|integer',
            'cart.*.quantity' => 'required|numeric|min:0.01',
            'cart.*.price' => 'nullable|numeric|min:0',
            'payment_method' => 'required_without:payments|string',
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|string',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'edit_reason' => 'nullable|string|max:255',
        ]);

        $sale = Sale::findOrFail($validated['original_sale_id']);
        $items = collect($validated['cart'])->map(fn ($i) => [
            'product_id' => $i['id'], 'quantity' => $i['quantity'], 'price' => $i['price'] ?? 0,
        ])->all();
        // One payment method: it covers whatever the new total is after the editor applies the real prices
        $payments = $validated['payments'] ?? [['method' => $validated['payment_method'], 'amount' => null]];

        try {
            $sale = $editor->update($sale, $items, $payments, trim($validated['edit_reason'] ?? '') ?: 'Edited from POS screen', 'POS');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'error' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully',
            'sale_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'total_amount' => $sale->total_amount,
        ]);
    }

    public function recentSales()
    {
        // Get recent sales with items and products
        $query = Sale::with(['items.product', 'items.product.brand']);

        // Data isolation: Cashiers only see their own sales
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            $query->whereHas('register', function($q) {
                $q->where('user_id', auth()->id());
            });
        }

        $sales = $query->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('pos.recent-sales', compact('sales'));
    }
}
