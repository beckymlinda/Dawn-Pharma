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
    /**
     * Cache of the next available numeric suffix for auto-generated
     * "PRD-xxxxx" SKUs. Kept as an instance property (not a static
     * inside a method) so it is computed once per request and shared
     * between store() and bulkUpload() if both were ever called in the
     * same lifecycle, and so tests can reset it between runs.
     */
    private ?int $nextSkuNum = null;

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
            $products = $query->latest()->paginate($limit);
        }

        // Stats for the modern UI
        $stats = [
            'total' => Product::count(),
            'low_stock' => Product::lowStock()->count(),
            'categories' => Category::count(),
            'total_value' => Product::sum(DB::raw('current_stock * purchase_price'))
        ];
        
        if ($request->ajax()) {
            return view('pos.products.table', compact('products'))->render();
        }
        
        return view('pos.products.index', compact('products', 'stats'));
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

            // Auto-generate SKU if empty (uses the same generator as bulkUpload())
            if (empty($data['sku'])) {
                $reserved = [];
                $data['sku'] = $this->generateUniqueSku($reserved);
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image'] = $imagePath;
            }

            // Set business location and product type
            $data['business_location'] = setting('system_name');
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
                    'business_location' => setting('system_name'),
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
                    // Nullable FK - no locations table needed, NULL skips the FK check entirely.
                    'business_location_id' => null,
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

    /**
     * Single source of truth for auto-generated SKUs.
     *
     * This replaces the two separate (and inconsistent) numbering
     * schemes that used to live in store() (based on last product ID)
     * and bulkUpload() (based on parsing the highest existing "PRD-xxxxx"
     * SKU). Both entry points now call this method, so they can never
     * hand out the same number to two different products.
     *
     * @param array $reserved  SKUs already claimed earlier in the *current*
     *                         request/batch but not yet committed to the DB
     *                         (e.g. other rows in the same CSV upload).
     */
    private function generateUniqueSku(array &$reserved): string
    {
        if ($this->nextSkuNum === null) {
            // Seed the counter from the highest numeric suffix currently
            // in use among "PRD-xxxxx" SKUs, not from the max product ID -
            // IDs and SKU numbers can drift apart (deletions, imported
            // products with custom SKUs, etc).
            $maxNum = Product::where('sku', 'LIKE', 'PRD-%')
                ->pluck('sku')
                ->map(function ($sku) {
                    return preg_match('/PRD-(\d+)/', $sku, $m) ? (int) $m[1] : 0;
                })
                ->max();

            $this->nextSkuNum = ($maxNum ?? 0) + 1;
        }

        do {
            $candidate = 'PRD-' . str_pad($this->nextSkuNum, 5, '0', STR_PAD_LEFT);
            $this->nextSkuNum++;
        } while (
            in_array($candidate, $reserved, true)
            || Product::where('sku', $candidate)->exists()
        );

        $reserved[] = $candidate;

        return $candidate;
    }

    /**
     * Detect a unique-constraint violation across DB drivers.
     *
     * The old code only checked $e->errorInfo[1] == 1062, which is
     * MySQL-specific. On SQLite / Postgres / SQL Server the driver error
     * code is different, so that check silently failed to match and the
     * duplicate SKU error surfaced to the user instead of being skipped
     * gracefully. This checks the underlying SQLSTATE plus a message
     * fallback so it works regardless of which DB_CONNECTION is active.
     */
    private function isUniqueConstraintViolation(\Illuminate\Database\QueryException $e): bool
    {
        // SQLSTATE 23000 = integrity constraint violation (MySQL, SQLite via PDO)
        // SQLSTATE 23505 = unique_violation (Postgres)
        $sqlState = $e->errorInfo[0] ?? null;
        if (in_array($sqlState, ['23000', '23505'], true)) {
            return true;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'duplicate key')
            || str_contains($message, 'unique_violation')
            || str_contains($message, 'integrity constraint violation');
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
                'business_location' => setting('system_name'),
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
                    // Nullable FK - no locations table needed, NULL skips the FK check entirely.
                    'business_location_id' => null,
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

    /**
     * Download a template for bulk product upload.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_upload_template.csv"',
        ];

        $columns = [
            'Name', 'SKU', 'Description', 'Purchase Price', 'Selling Price', 
            'Current Stock', 'Alert Quantity', 'Expiry Date (YYYY-MM-DD)', 
            'Category Name', 'Brand Name', 'Unit Name', 'Supplier Name'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add a sample row
            fputcsv($file, [
                'Paracetamol 500mg', 'PRD-00001', 'Pain reliever and a fever reducer', '1500', '2500', 
                '100', '10', '2027-12-31', 'Pain Killers', 'Generic', 'Pack', 'Main Distributor'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Handle bulk product upload from CSV.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'upload_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        // Increase execution time and memory for large uploads
        set_time_limit(600); // 10 minutes
        ini_set('memory_limit', '512M');

        $file = $request->file('upload_file');
        $filePath = $file->getRealPath();
        
        // Detect delimiter
        $firstLine = file_get_contents($filePath, false, null, 0, 1024);
        $delimiter = ','; // default
        if (strpos($firstLine, "\t") !== false) {
            $delimiter = "\t";
        } elseif (strpos($firstLine, ';') !== false && strpos($firstLine, ',') === false) {
            $delimiter = ';';
        }
        
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 0, $delimiter); // Read header row

        $headerMap = [];
        if (is_array($header)) {
            foreach ($header as $columnIndex => $columnName) {
                $normalized = trim(strtolower(str_replace(['\xEF\xBB\xBF', '\r', '\n', '\t'], '', $columnName)));
                $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);

                switch ($normalized) {
                    case 'name':
                    case 'product_name':
                        $headerMap['name'] = $columnIndex;
                        break;
                    case 'sku':
                    case 'stock_keeping_unit':
                        $headerMap['sku'] = $columnIndex;
                        break;
                    case 'description':
                        $headerMap['description'] = $columnIndex;
                        break;
                    case 'purchase_price':
                    case 'purchase_pr':
                    case 'purchase_price_mwk':
                        $headerMap['purchase_price'] = $columnIndex;
                        break;
                    case 'selling_price':
                    case 'selling_pr':
                        $headerMap['selling_price'] = $columnIndex;
                        break;
                    case 'current_stock':
                    case 'current_stoc':
                    case 'current_stock_qty':
                        $headerMap['current_stock'] = $columnIndex;
                        break;
                    case 'alert_quantity':
                    case 'alert_quanti':
                    case 'alert_qty':
                        $headerMap['alert_quantity'] = $columnIndex;
                        break;
                    case 'expiry_date':
                    case 'expiry_date_yyyy_mm_dd':
                    case 'expiry':
                        $headerMap['expiry_date'] = $columnIndex;
                        break;
                    case 'category_name':
                    case 'category':
                        $headerMap['category_name'] = $columnIndex;
                        break;
                    case 'brand_name':
                    case 'brand':
                    case 'manufacturer':
                        $headerMap['brand_name'] = $columnIndex;
                        break;
                    case 'unit_name':
                    case 'unit':
                        $headerMap['unit_name'] = $columnIndex;
                        break;
                    case 'supplier_name':
                    case 'supplier':
                        $headerMap['supplier_name'] = $columnIndex;
                        break;
                }
            }
        }

        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;
        $errors = [];

        // Cache for foreign keys to avoid redundant queries
        $categories = Category::pluck('id', 'name')->toArray();
        $brands = Brand::pluck('id', 'name')->toArray();
        $units = Unit::pluck('id', 'name')->toArray();
        $suppliers = Contact::where('type', 'supplier')->pluck('id', 'name')->toArray();

        // Build normalized name -> id maps for case-insensitive lookups
        $unitsNormalized = [];
        foreach ($units as $uName => $uId) {
            $unitsNormalized[strtolower(trim($uName))] = $uId;
        }

        // Track SKUs and Names processed in this batch to avoid duplicates within the same CSV
        $processedSkus = [];
        $processedNames = [];
        
        // Track newly created categories, brands, and units to avoid duplicate creation within batch
        $newCategoriesCreated = [];
        $newBrandsCreated = [];
        $newUnitsCreated = [];

        // Reset the shared SKU counter so it re-seeds from the current DB
        // state at the start of this upload (see generateUniqueSku()).
        $this->nextSkuNum = null;

        try {
            DB::beginTransaction();

            $rowNumber = 1; // Start after header
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                $nameIndex = $headerMap['name'] ?? 0;
                $skuIndex = $headerMap['sku'] ?? 1;
                $descriptionIndex = $headerMap['description'] ?? 2;
                $purchasePriceIndex = $headerMap['purchase_price'] ?? 3;
                $sellingPriceIndex = $headerMap['selling_price'] ?? 4;
                $currentStockIndex = $headerMap['current_stock'] ?? 5;
                $alertQuantityIndex = $headerMap['alert_quantity'] ?? 6;
                $expiryDateIndex = $headerMap['expiry_date'] ?? 7;
                $categoryNameIndex = $headerMap['category_name'] ?? 8;
                $brandNameIndex = $headerMap['brand_name'] ?? 9;
                $unitNameIndex = $headerMap['unit_name'] ?? 10;
                $supplierNameIndex = $headerMap['supplier_name'] ?? 11;

                // Basic validation: row must have data
                if (empty($row) || !isset($row[$nameIndex]) || trim($row[$nameIndex] ?? '') === '') {
                    continue;
                }

                $name = trim($row[$nameIndex] ?? '');
                $sku = !empty($row[$skuIndex]) ? trim($row[$skuIndex]) : null;
                $nameLower = strtolower($name);

                // Check if Name or SKU was already seen in this CSV batch
                if (in_array($nameLower, $processedNames) || ($sku && in_array($sku, $processedSkus))) {
                    $skipCount++;
                    continue;
                }

                // Check if already exists in DB (by SKU or Name)
                $existing = null;
                if ($sku) {
                    $existing = Product::where('sku', $sku)->first();
                } else {
                    $existing = Product::where('name', $name)->first();
                }

                if ($existing) {
                    $skipCount++;
                    $processedNames[] = $nameLower;
                    if ($sku) $processedSkus[] = $sku;
                    continue;
                }

                // Map foreign keys - auto-create category and brand if not found
                $categoryId = null;
                if (!empty($row[$categoryNameIndex])) {
                    $catName = trim($row[$categoryNameIndex]);
                    if ($catName !== '') {
                        if (isset($categories[$catName])) {
                            $categoryId = $categories[$catName];
                            \Log::info('BulkUpload: using existing category from cache', ['name' => $catName, 'id' => $categoryId, 'row' => $rowNumber]);
                        } else {
                            try {
                                $category = Category::firstOrCreate(
                                    ['name' => $catName],
                                    ['category_code' => null, 'description' => null]
                                );
                                $categoryId = $category->id;
                                $categories[$catName] = $categoryId;
                                \Log::info('BulkUpload: created or found category', ['name' => $catName, 'id' => $categoryId, 'row' => $rowNumber]);
                            } catch (\Exception $e) {
                                \Log::error('BulkUpload: failed to create category', ['name' => $catName, 'error' => $e->getMessage(), 'row' => $rowNumber]);
                                // If creation fails, leave category null and continue
                            }
                        }
                    }
                }

                $brandId = null;
                if (!empty($row[$brandNameIndex])) {
                    $brandName = trim($row[$brandNameIndex]);
                    if ($brandName !== '') {
                        if (isset($brands[$brandName])) {
                            $brandId = $brands[$brandName];
                            \Log::info('BulkUpload: using existing brand from cache', ['name' => $brandName, 'id' => $brandId, 'row' => $rowNumber]);
                        } else {
                            try {
                                $brand = Brand::firstOrCreate(
                                    ['name' => $brandName],
                                    ['note' => null]
                                );
                                $brandId = $brand->id;
                                $brands[$brandName] = $brandId;
                                \Log::info('BulkUpload: created or found brand', ['name' => $brandName, 'id' => $brandId, 'row' => $rowNumber]);
                            } catch (\Exception $e) {
                                \Log::error('BulkUpload: failed to create brand', ['name' => $brandName, 'error' => $e->getMessage(), 'row' => $rowNumber]);
                                // If creation fails, leave brand null and continue
                            }
                        }
                    }
                }

                $unitId = null;
                if (!empty($row[$unitNameIndex])) {
                    $unitName = trim($row[$unitNameIndex]);
                    if ($unitName !== '') {
                        $unitKey = strtolower($unitName);
                        if (isset($unitsNormalized[$unitKey])) {
                            $unitId = $unitsNormalized[$unitKey];
                            \Log::info('BulkUpload: using existing unit from normalized map', ['name' => $unitName, 'id' => $unitId, 'row' => $rowNumber]);
                        } elseif (isset($newUnitsCreated[$unitKey])) {
                            $unitId = $newUnitsCreated[$unitKey];
                            \Log::info('BulkUpload: using newly created unit from cache', ['name' => $unitName, 'id' => $unitId, 'row' => $rowNumber]);
                        } else {
                            try {
                                $unit = Unit::firstOrCreate(['name' => $unitName], ['short_name' => null, 'allow_decimal' => 1]);
                                $unitId = $unit->id;
                                $units[$unitName] = $unitId;
                                $unitsNormalized[$unitKey] = $unitId;
                                $newUnitsCreated[$unitKey] = $unitId;
                                \Log::info('BulkUpload: created or found unit', ['name' => $unitName, 'id' => $unitId, 'row' => $rowNumber]);
                            } catch (\Exception $e) {
                                \Log::error('BulkUpload: failed to create unit', ['name' => $unitName, 'error' => $e->getMessage(), 'row' => $rowNumber]);
                            }
                        }
                    }
                }

                $supplierId = null;
                if (!empty($row[$supplierNameIndex])) {
                    $supName = trim($row[$supplierNameIndex]);
                    if ($supName !== '') {
                        if (isset($suppliers[$supName])) {
                            $supplierId = $suppliers[$supName];
                            \Log::info('BulkUpload: using existing supplier from cache', ['name' => $supName, 'id' => $supplierId, 'row' => $rowNumber]);
                        } else {
                            try {
                                $supplier = Contact::firstOrCreate(
                                    ['name' => $supName, 'type' => 'supplier'],
                                    ['email' => null, 'phone' => null, 'address' => null, 'city' => null, 'country' => null, 'notes' => null, 'contact_person' => null, 'tax_number' => null, 'created_by' => auth()->id()]
                                );
                                $supplierId = $supplier->id;
                                $suppliers[$supName] = $supplierId;
                                \Log::info('BulkUpload: created or found supplier', ['name' => $supName, 'id' => $supplierId, 'row' => $rowNumber]);
                            } catch (\Exception $e) {
                                \Log::error('BulkUpload: failed to create supplier', ['name' => $supName, 'error' => $e->getMessage(), 'row' => $rowNumber]);
                            }
                        }
                    }
                }

                // Prepare date
                $expiryDate = null;
                if (!empty($row[$expiryDateIndex])) {
                    try { $expiryDate = \Carbon\Carbon::parse($row[$expiryDateIndex])->format('Y-m-d'); } catch (\Exception $e) {}
                }

                $productData = [
                    'name' => $name,
                    'sku' => $sku,
                    'description' => $row[$descriptionIndex] ?? null,
                    'purchase_price' => !empty($row[$purchasePriceIndex]) ? (float)$row[$purchasePriceIndex] : 0,
                    'selling_price' => !empty($row[$sellingPriceIndex]) ? (float)$row[$sellingPriceIndex] : 0,
                    'current_stock' => !empty($row[$currentStockIndex]) ? (float)$row[$currentStockIndex] : 0,
                    'alert_quantity' => !empty($row[$alertQuantityIndex]) ? (float)$row[$alertQuantityIndex] : 10,
                    'expiry_date' => $expiryDate,
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'unit_id' => $unitId,
                    'supplier_id' => $supplierId,
                    'business_location' => setting('system_name'),
                    'product_type' => 'Single',
                    'last_purchase_price' => !empty($row[3]) ? (float)$row[3] : 0,
                    'last_purchase_date' => now(),
                ];

                // Auto-generate SKU if still empty - uses the SAME generator
                // as store(), seeded once per upload above.
                if (empty($productData['sku'])) {
                    $productData['sku'] = $this->generateUniqueSku($processedSkus);
                }

                try {
                    $product = Product::create($productData);
                    
                    $processedNames[] = $nameLower;
                    if ($product->sku) $processedSkus[] = $product->sku;
                    
                    $successCount++;

                    if ($productData['current_stock'] > 0) {
                        StockHistory::create([
                            'product_id' => $product->id,
                            'type' => 'Opening Stock',
                            'quantity_change' => $productData['current_stock'],
                            'new_quantity' => $productData['current_stock'],
                            'unit_cost' => $productData['purchase_price'],
                            'date' => now(),
                            'reference_no' => 'BULK-IMPORT-' . now()->format('Ymd'),
                            'customer_supplier' => 'Bulk Import',
                            // Nullable FK - no locations table needed, NULL skips the FK check entirely.
                            'business_location_id' => null,
                        ]);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($this->isUniqueConstraintViolation($e)) {
                        $skipCount++;
                        $processedNames[] = $nameLower;
                        if ($sku) $processedSkus[] = $sku;
                        continue;
                    }
                    $errorCount++;
                    $errors[] = "Row $rowNumber ($name): " . $e->getMessage();
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber ($name): " . $e->getMessage();
                }
            }

            DB::commit();
            fclose($handle);

            $message = "Upload Summary: $successCount created, $skipCount skipped (already exist), $errorCount errors.";

            if ($errorCount > 0) {
                return redirect()->back()->with('success', $message)->with('error_details', array_slice($errors, 0, 50)); // Limit error display
            }

            return redirect()->route('products.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            if (isset($handle)) fclose($handle);
            return redirect()->back()->with('error', 'Critical System Error: ' . $e->getMessage());
        }
    }
}