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

    /** CSV column names (lower case, punctuation as "_") => product field */
    private const BULK_COLUMNS = [
        'name' => 'name', 'product_name' => 'name', 'product' => 'name',
        'sku' => 'sku', 'stock_keeping_unit' => 'sku', 'code' => 'sku', 'barcode' => 'sku',
        'description' => 'description',
        'purchase_price' => 'purchase_price', 'purchase_pr' => 'purchase_price', 'purchase_price_mwk' => 'purchase_price', 'cost_price' => 'purchase_price', 'cost' => 'purchase_price',
        'selling_price' => 'selling_price', 'selling_pr' => 'selling_price', 'selling_price_mwk' => 'selling_price', 'price' => 'selling_price',
        'current_stock' => 'current_stock', 'current_stoc' => 'current_stock', 'current_stock_qty' => 'current_stock', 'stock' => 'current_stock', 'quantity' => 'current_stock', 'qty' => 'current_stock',
        'alert_quantity' => 'alert_quantity', 'alert_quanti' => 'alert_quantity', 'alert_qty' => 'alert_quantity', 'reorder_level' => 'alert_quantity',
        'expiry_date' => 'expiry_date', 'expiry_date_yyyy_mm_dd' => 'expiry_date', 'expiry' => 'expiry_date', 'expiry_date_dd_mm_yyyy' => 'expiry_date',
        'category_name' => 'category_name', 'category' => 'category_name',
        'brand_name' => 'brand_name', 'brand' => 'brand_name', 'manufacturer' => 'brand_name',
        'unit_name' => 'unit_name', 'unit' => 'unit_name',
        'supplier_name' => 'supplier_name', 'supplier' => 'supplier_name',
    ];

    /** Column order of the downloadable template (used when a file has no recognisable header) */
    private const TEMPLATE_ORDER = ['name', 'sku', 'description', 'purchase_price', 'selling_price', 'current_stock',
        'alert_quantity', 'expiry_date', 'category_name', 'brand_name', 'unit_name', 'supplier_name'];

    /**
     * "12,500", "MWK 18,000.50", " 300 " → number; "" → null; anything else → false.
     */
    private function bulkNumber(?string $value): float|null|false
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $clean = str_replace([',', ' ', "\u{00A0}"], '', preg_replace('/^(mwk|mk|k)\s*/i', '', $value));

        return is_numeric($clean) ? (float) $clean : false;
    }

    /**
     * Expiry dates as people type them in Malawi: day first (31/12/2027, 31-12-2027,
     * 31.12.27), ISO (2027-12-31), "31 Dec 2027", or an Excel date number. Null when empty,
     * false when it cannot be understood.
     */
    private function bulkDate(?string $value): string|null|false
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{5}$/', $value)) {   // Excel stores dates as days since 1899-12-30
            return \Carbon\Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y', 'd.m.y', 'Y/m/d', 'm/Y', 'm-Y'] as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $value);
            $errors = \DateTime::getLastErrors();
            if ($date && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                // "12/2027" means the end of that month, as printed on packs
                return in_array($format, ['m/Y', 'm-Y'], true) ? $date->format('Y-m-t') : $date->format('Y-m-d');
            }
        }
        if (preg_match('/[a-z]/i', $value)) {   // "31 Dec 2027", "Dec 2027"
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        return false;
    }

    /**
     * Find a category/brand/unit/supplier by name whatever its case, or create it.
     */
    private function bulkLookup(array &$cache, string $name, callable $create): ?int
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return null;
        }
        $key = mb_strtolower($name);
        if (!array_key_exists($key, $cache)) {
            $cache[$key] = $create($name)->id;
        }

        return $cache[$key];
    }

    /**
     * Handle bulk product upload from CSV.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'upload_file' => 'required|file|mimes:csv,txt|max:5120',
        ], [
            'upload_file.mimes' => 'Upload a CSV file. In Excel use File > Save As > "CSV UTF-8 (Comma delimited)".',
        ]);

        // Increase execution time and memory for large uploads
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $contents = file_get_contents($request->file('upload_file')->getRealPath());
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);   // Excel's "CSV UTF-8" marker
        if (!mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');   // older Excel "CSV"
        }

        // Delimiter: whichever of , ; tab appears most in the header line
        $firstLine = strtok($contents, "\r\n") ?: '';
        $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);
        $delimiter = array_key_first($counts);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        // Columns by their header names, in any order; the template order if none are recognised
        $header = fgetcsv($handle, 0, $delimiter) ?: [];
        $columns = [];
        foreach ($header as $index => $title) {
            $normalized = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $title))), '_');
            if (isset(self::BULK_COLUMNS[$normalized]) && !isset($columns[self::BULK_COLUMNS[$normalized]])) {
                $columns[self::BULK_COLUMNS[$normalized]] = $index;
            }
        }
        if (!isset($columns['name'])) {
            $columns = array_flip(self::TEMPLATE_ORDER);
        }
        $cell = fn (array $row, string $field) => isset($columns[$field]) ? trim((string) ($row[$columns[$field]] ?? '')) : '';

        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;
        $errors = [];

        // Existing names (lower case) so each is looked up once, whatever its case
        $names = fn ($query) => $query->get(['id', 'name'])->mapWithKeys(fn ($r) => [mb_strtolower(trim($r->name)) => $r->id])->all();
        $categories = $names(Category::query());
        $brands = $names(Brand::query());
        $units = $names(Unit::query());
        $suppliers = $names(Contact::where('type', 'supplier'));

        // SKUs and names already in this shop or earlier in this file
        $existingSkus = array_change_key_case(array_flip(Product::whereNotNull('sku')->pluck('sku')->all()));
        $existingNames = array_flip(Product::pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->all());
        $processedSkus = [];

        // Reset the shared SKU counter so it re-seeds from the current DB
        // state at the start of this upload (see generateUniqueSku()).
        $this->nextSkuNum = null;

        try {
            DB::beginTransaction();

            $rowNumber = 1;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                $name = $cell($row, 'name');
                if ($name === '') {
                    continue;   // blank line
                }
                $sku = $cell($row, 'sku') ?: null;
                $nameKey = mb_strtolower($name);

                // Already in the shop, or earlier in this file (SKU, or the name when there is no SKU)
                if (($sku && isset($existingSkus[mb_strtolower($sku)])) || (!$sku && isset($existingNames[$nameKey]))) {
                    $skipCount++;
                    continue;
                }

                $purchase = $this->bulkNumber($cell($row, 'purchase_price'));
                $selling = $this->bulkNumber($cell($row, 'selling_price'));
                $stock = $this->bulkNumber($cell($row, 'current_stock'));
                $alert = $this->bulkNumber($cell($row, 'alert_quantity'));
                $expiry = $this->bulkDate($cell($row, 'expiry_date'));

                $problems = [];
                if ($selling === null || $selling === false || $selling <= 0) {
                    $problems[] = 'selling price is missing or not a number';
                }
                foreach (['purchase price' => $purchase, 'stock' => $stock, 'alert quantity' => $alert] as $label => $value) {
                    if ($value === false || ($value !== null && $value < 0)) {
                        $problems[] = "$label \"" . $cell($row, str_replace(' ', '_', $label === 'stock' ? 'current_stock' : $label)) . '" is not a valid number';
                    }
                }
                if ($expiry === false) {
                    $problems[] = 'expiry date "' . $cell($row, 'expiry_date') . '" is not a date (use 31/12/2027)';
                }
                if ($problems) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber ($name): " . implode('; ', $problems) . '.';
                    continue;
                }

                try {
                    $productData = [
                        'name' => $name,
                        'sku' => $sku ?? $this->generateUniqueSku($processedSkus),
                        'description' => $cell($row, 'description') ?: null,
                        'purchase_price' => $purchase ?? 0,
                        'selling_price' => $selling,
                        'current_stock' => $stock ?? 0,
                        'alert_quantity' => $alert ?? 10,
                        'expiry_date' => $expiry,
                        'category_id' => $this->bulkLookup($categories, $cell($row, 'category_name'),
                            fn ($n) => Category::create(['name' => $n, 'category_code' => null, 'description' => null])),
                        'brand_id' => $this->bulkLookup($brands, $cell($row, 'brand_name'),
                            fn ($n) => Brand::create(['name' => $n, 'note' => null])),
                        'unit_id' => $this->bulkLookup($units, $cell($row, 'unit_name'),
                            fn ($n) => Unit::create(['name' => $n, 'short_name' => mb_substr($n, 0, 10), 'allow_decimal' => 1])),
                        'supplier_id' => $this->bulkLookup($suppliers, $cell($row, 'supplier_name'),
                            fn ($n) => Contact::create(['name' => $n, 'type' => 'supplier', 'created_by' => auth()->id()])),
                        'business_location' => setting('system_name'),
                        'product_type' => 'Single',
                        'last_purchase_price' => $purchase ?? 0,
                        'last_purchase_date' => now(),
                    ];

                    $product = Product::create($productData);

                    $existingSkus[mb_strtolower($product->sku)] = true;
                    $existingNames[$nameKey] = true;
                    $processedSkus[] = $product->sku;
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
                        continue;
                    }
                    $errorCount++;
                    $errors[] = "Row $rowNumber ($name): could not be saved.";
                    \Log::error('BulkUpload: row failed', ['row' => $rowNumber, 'error' => $e->getMessage()]);
                }
            }

            DB::commit();
            fclose($handle);

            $message = "Upload Summary: $successCount created, $skipCount skipped (already exist), $errorCount errors.";

            if ($errorCount > 0) {
                return redirect()->back()->with('success', $message)->with('error_details', array_slice($errors, 0, 50));
            }

            return redirect()->route('products.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            fclose($handle);
            \Log::error('BulkUpload: failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'The upload failed and nothing was saved. Check the file and try again.');
        }
    }
}
