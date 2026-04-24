<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Contact;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $limit = $request->get('limit', 25);
        
        $query = Product::with(['unit', 'category', 'brand', 'supplier']);
        
        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhereHas('category', function($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('brand', function($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  });
            });
        }
        
        // Apply pagination limit
        if ($limit === 'all') {
            $products = $query->get();
            // Convert to LengthAwarePaginator for consistency
            $products = new \Illuminate\Pagination\LengthAwarePaginator(
                $products,
                $products->count(),
                $products->count(),
                1,
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            );
        } else {
            $products = $query->paginate($limit);
        }
        
        return view('pos.products.index', compact('products'));
    }

    public function create()
    {
        $units = Unit::all();
        $categories = Category::all();
        $brands = Brand::all();
        $suppliers = Contact::where('type', 'supplier')->get();
        
        // Get first brand and category for default selection
        $firstBrand = $brands->first();
        $firstCategory = $categories->first();
        $firstSupplier = $suppliers->first();
        
        return view('pos.add-product', compact('units', 'categories', 'brands', 'suppliers', 'firstBrand', 'firstCategory', 'firstSupplier'));
    }

    public function store(Request $request)
    {
        // Debug: Log incoming request data
        \Log::info('Product Store - Request Data', [
            'all_data' => $request->all(),
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'supplier_id' => $request->supplier_id,
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'supplier_id' => 'nullable|exists:contacts,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'alert_quantity' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'purchase_quantity' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'reference_no' => 'nullable|string',
        ], [
            'category_id.exists' => 'The selected category is invalid.',
            'brand_id.exists' => 'The selected brand is invalid.',
            'supplier_id.exists' => 'The selected supplier is invalid.',
            'unit_id.exists' => 'The selected unit is invalid.',
        ]);

        // Debug: Log validation success
        \Log::info('Product Store - Validation Success', [
            'validated_data' => $data,
            'category_id_value' => $data['category_id'] ?? 'null',
            'brand_id_value' => $data['brand_id'] ?? 'null',
        ]);

        try {
            DB::beginTransaction();

            // Auto-generate SKU if empty
            if (empty($data['sku'])) {
                $lastProduct = Product::orderBy('id', 'desc')->first();
                $nextId = ($lastProduct ? $lastProduct->id : 0) + 1;
                $data['sku'] = 'PRD-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image'] = $imagePath;
            }

            // Set business location and product type
            $data['business_location'] = 'EDUC Pharmacy (BL0001)';
            $data['product_type'] = 'Single';
            
            // Set purchase tracking fields
            $purchaseQuantity = $data['purchase_quantity'] ?? 0;
            $data['current_stock'] = $purchaseQuantity;
            $data['last_purchase_price'] = $data['purchase_price'];
            $data['last_purchase_date'] = $data['purchase_date'] ?? now();

            // Create product
            \Log::info('Product Store - About to Create Product', [
                'data_for_creation' => $data,
                'business_location' => $data['business_location'],
                'product_type' => $data['product_type'],
            ]);
            
            $product = Product::create($data);
            
            \Log::info('Product Store - Product Created', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
            ]);

            // Create purchase record if quantity provided
            if ($purchaseQuantity > 0) {
                $referenceNo = $data['reference_no'] ?? $this->generatePurchaseReference();
                $purchaseDate = $data['purchase_date'] ?? now();
                $totalCost = $purchaseQuantity * $data['purchase_price'];

                // Create purchase
                $purchase = Purchase::create([
                    'supplier_id' => $data['supplier_id'],
                    'business_location' => 'EDUC Pharmacy (BL0001)',
                    'reference_no' => $referenceNo,
                    'purchase_date' => $purchaseDate,
                    'status' => 'received',
                    'payment_status' => 'due',
                    'grand_total' => $totalCost,
                    'created_by' => auth()->id(),
                ]);

                // Create purchase item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $purchaseQuantity,
                    'unit_cost' => $data['purchase_price'],
                    'expiry_date' => $data['expiry_date'],
                    'discount_percent' => 0,
                    'tax_percent' => 0,
                    'line_total' => $totalCost,
                ]);

                // Create stock history
                StockHistory::create([
                    'product_id' => $product->id,
                    'type' => 'Purchase',
                    'quantity_change' => $purchaseQuantity,
                    'new_quantity' => $purchaseQuantity,
                    'unit_cost' => $data['purchase_price'],
                    'date' => $purchaseDate,
                    'reference_no' => $referenceNo,
                    'customer_supplier' => $product->supplier->name ?? 'Unknown Supplier',
                    'business_location_id' => 1,
                ]);
            }

            DB::commit();

            return redirect()->route('products.index')->with('success', 'Product added successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            
            // Debug: Log the exception
            \Log::error('Product Store - Exception Caught', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to add product: ' . $e->getMessage())->withInput();
        }
    }

    private function generatePurchaseReference()
    {
        $today = now();
        $year = $today->format('Y');
        $month = $today->format('m');
        $day = $today->format('d');
        $random = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        return "PUR-{$year}{$month}{$day}-{$random}";
    }

    public function show(Product $product)
    {
        return $product->load(['unit', 'category', 'brand']);
    }

    public function edit(Product $product)
    {
        $units = Unit::all();
        $categories = Category::all();
        $brands = Brand::all();
        $suppliers = Contact::where('type', 'supplier')->get();
        
        return view('pos.edit-product', compact('product', 'units', 'categories', 'brands', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'alert_quantity' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $data['image'] = $imagePath;
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully!');
    }

    public function getStockInfo(Product $product)
    {
        return response()->json([
            'current_stock' => $product->current_stock,
            'selling_price' => $product->selling_price,
            'purchase_price' => $product->purchase_price ?? 0,
            'name' => $product->name,
            'sku' => $product->sku
        ]);
    }

    public function purchaseCreate()
    {
        $suppliers = Contact::where('type', 'supplier')->get();
        $products = Product::with(['unit', 'category', 'brand', 'supplier'])->get();
        $recentPurchases = Purchase::with(['supplier', 'items.product'])
            ->latest()
            ->take(10)
            ->get();
        
        return view('pos.product-purchase', compact('suppliers', 'products', 'recentPurchases'));
    }

    public function purchaseStore(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:contacts,id',
            'purchase_date' => 'required|date',
            'reference_no' => 'nullable|string|unique:purchases,reference_no',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_cost' => 'required|numeric|min:0',
            'products.*.expiry_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            // Generate reference number if not provided
            if (empty($data['reference_no'])) {
                $data['reference_no'] = $this->generatePurchaseReference();
            }

            // Calculate grand total
            $grandTotal = 0;
            foreach ($data['products'] as $product) {
                $grandTotal += $product['quantity'] * $product['unit_cost'];
            }

            // Create purchase
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'business_location' => 'EDUC Pharmacy (BL0001)',
                'reference_no' => $data['reference_no'],
                'purchase_date' => $data['purchase_date'],
                'status' => 'received',
                'payment_status' => 'due',
                'grand_total' => $grandTotal,
                'created_by' => auth()->id(),
            ]);

            // Create purchase items and update stock
            foreach ($data['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                $quantity = $productData['quantity'];
                $unitCost = $productData['unit_cost'];
                $lineTotal = $quantity * $unitCost;

                // Create purchase item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'expiry_date' => $productData['expiry_date'] ?? null,
                    'discount_percent' => 0,
                    'tax_percent' => 0,
                    'line_total' => $lineTotal,
                ]);

                // Update product stock and purchase tracking
                $oldStock = $product->current_stock ?? 0;
                $newStock = $oldStock + $quantity;
                
                $product->update([
                    'current_stock' => $newStock,
                    'stock' => $newStock,
                    'last_purchase_price' => $unitCost,
                    'last_purchase_date' => $data['purchase_date'],
                ]);

                // Create stock history
                StockHistory::create([
                    'product_id' => $productData['product_id'],
                    'type' => 'Purchase',
                    'quantity_change' => $quantity,
                    'new_quantity' => $newStock,
                    'unit_cost' => $unitCost,
                    'date' => $data['purchase_date'],
                    'reference_no' => $data['reference_no'],
                    'customer_supplier' => $purchase->supplier->name ?? 'Unknown Supplier',
                    'business_location_id' => 1,
                ]);
            }

            DB::commit();

            return redirect()->route('products.purchase.create')
                ->with('success', 'Purchase completed successfully! Reference: ' . $data['reference_no']);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to complete purchase: ' . $e->getMessage())
                ->withInput();
        }
    }
}
