<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['product', 'user'])
            ->orderBy('created_at', 'desc');
        
        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $filterType = $request->input('filter', 'all');
            
            $query->where(function($q) use ($searchTerm, $filterType) {
                switch ($filterType) {
                    case 'product':
                        $q->whereHas('product', function($productQuery) use ($searchTerm) {
                            $productQuery->where('name', 'like', "%{$searchTerm}%")
                                      ->orWhere('sku', 'like', "%{$searchTerm}%");
                        });
                        break;
                        
                    case 'reason':
                        $q->where('reason', 'like', "%{$searchTerm}%");
                        break;
                        
                    case 'user':
                        $q->whereHas('user', function($userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                        break;
                        
                    case 'type':
                        $q->where('adjustment_type', 'like', "%{$searchTerm}%");
                        break;
                        
                    default:
                        // All fields search (original behavior)
                        $q->whereHas('product', function($productQuery) use ($searchTerm) {
                            $productQuery->where('name', 'like', "%{$searchTerm}%")
                                          ->orWhere('sku', 'like', "%{$searchTerm}%");
                        })
                        ->orWhere('reason', 'like', "%{$searchTerm}%")
                        ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                        break;
                }
            });
        }
        
        $adjustments = $query->paginate(50);
            
        return view('stock-adjustments.index', compact('adjustments'));
    }

    public function create(Request $request)
    {
        $products = Product::orderBy('name')->get();
        $selectedProductId = $request->input('product_id');
        return view('stock-adjustments.create', compact('products', 'selectedProductId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|in:add,subtract',
            'quantity' => 'nullable|integer|min:0',
            'reason' => 'required|string|max:255',
            'new_selling_price' => 'nullable|numeric|min:0',
            'new_purchase_price' => 'nullable|numeric|min:0',
            'new_expiry_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($validated['product_id']);
            $previousQuantity = $product->current_stock ?? 0;
            $previousSellingPrice = $product->selling_price ?? 0;
            $previousPurchasePrice = $product->purchase_price ?? 0;
            
            // Debug: Log initial state
            \Log::info('Stock Adjustment - Initial State', [
                'product_id' => $validated['product_id'],
                'previous_quantity' => $previousQuantity,
                'previous_selling_price' => $previousSellingPrice,
                'validated_data' => $validated
            ]);

            // Handle price-only updates (when quantity is empty)
            if (empty($validated['quantity'])) {
                // Only update prices, keep quantity the same
                $newQuantity = $previousQuantity;
                
                // Update product prices only
                if (!empty($validated['new_selling_price'])) {
                    $product->selling_price = $validated['new_selling_price'];
                }
                
                if (!empty($validated['new_purchase_price'])) {
                    $product->purchase_price = $validated['new_purchase_price'];
                }
                
                if (!empty($validated['new_expiry_date'])) {
                    $product->expiry_date = $validated['new_expiry_date'];
                }
                
                $product->save();
                
                // Debug: Log after price-only save
                \Log::info('Stock Adjustment - After Price-Only Save', [
                    'product_id' => $product->id,
                    'new_selling_price' => $product->selling_price,
                    'current_stock' => $product->current_stock,
                    'changes_made' => 'Prices updated only'
                ]);
                
                // Create stock adjustment record for price update
                StockAdjustment::create([
                    'product_id' => $validated['product_id'],
                    'user_id' => auth()->id(),
                    'adjustment_type' => 'add', // Use 'add' since DB only allows add/subtract
                    'quantity' => 0, // No quantity change
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'previous_price' => $previousSellingPrice,
                    'new_price' => $validated['new_selling_price'] ?? $previousSellingPrice,
                    'new_expiry_date' => $validated['new_expiry_date'] ?? null,
                    'reason' => $validated['reason'] . ' (Price update only)',
                ]);
                
                DB::commit();
                
                return redirect()->route('stock-adjustments.index')
                    ->with('success', 'Price update completed successfully!');
            }
            
            // Handle quantity adjustment
            $newQuantity = $validated['adjustment_type'] === 'add' 
                ? $previousQuantity + $validated['quantity']
                : $previousQuantity - $validated['quantity'];

            if ($newQuantity < 0) {
                return back()->withErrors(['quantity' => 'Cannot subtract more than current stock.']);
            }

            // Update product
            $product->current_stock = $newQuantity;
            $product->stock = $newQuantity;
            
            if (!empty($validated['new_selling_price'])) {
                $product->selling_price = $validated['new_selling_price'];
            }
            
            if (!empty($validated['new_purchase_price'])) {
                $product->purchase_price = $validated['new_purchase_price'];
            }
            
            if (!empty($validated['new_expiry_date'])) {
                $product->expiry_date = $validated['new_expiry_date'];
            }

            $product->save();

            // Debug: Log after quantity adjustment save
            \Log::info('Stock Adjustment - After Quantity Save', [
                'product_id' => $product->id,
                'new_quantity' => $newQuantity,
                'current_stock' => $product->current_stock,
                'changes_made' => 'Quantity adjusted'
            ]);

            // Create stock adjustment record
            StockAdjustment::create([
                'product_id' => $validated['product_id'],
                'user_id' => auth()->id(),
                'adjustment_type' => $validated['adjustment_type'],
                'quantity' => $validated['quantity'],
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'previous_price' => $previousSellingPrice,
                'new_price' => $validated['new_selling_price'] ?? $previousSellingPrice,
                'new_expiry_date' => $validated['new_expiry_date'] ?? null,
                'reason' => $validated['reason'],
            ]);

            DB::commit();

            return redirect()->route('stock-adjustments.index')
                ->with('success', 'Stock adjustment completed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process stock adjustment: ' . $e->getMessage()]);
        }
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return view('stock-adjustments.show', compact('stockAdjustment'));
    }

    /**
     * Show the bulk stock adjustment upload page.
     */
    public function bulk()
    {
        abort_unless(auth()->user()->hasPermission('create_stock_adjustment'), 403);

        return view('stock-adjustments.bulk');
    }

    /**
     * Download a CSV template pre-filled with all existing products.
     * The user only needs to fill in "Quantity to Add" (prices/expiry optional).
     */
    public function downloadTemplate()
    {
        abort_unless(auth()->user()->hasPermission('create_stock_adjustment'), 403);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock_adjustment_template_' . date('Y-m-d') . '.csv"',
        ];

        $columns = [
            'SKU', 'Product Name', 'Current Stock (reference only)', 'Quantity to Add',
            'New Purchase Price', 'New Selling Price', 'New Expiry Date (YYYY-MM-DD)', 'Reason',
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            Product::orderBy('name')
                ->select(['id', 'sku', 'name', 'current_stock'])
                ->chunk(500, function ($products) use ($file) {
                    foreach ($products as $product) {
                        fputcsv($file, [
                            $product->sku, $product->name, (float) ($product->current_stock ?? 0),
                            '', '', '', '', '',
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Handle bulk stock adjustment upload from CSV.
     *
     * Only adds stock to products that already exist (matched by SKU, then by name).
     * Products are never created here, rows repeating a product within the file are
     * skipped, and uploading the same data twice is rejected so stock is not doubled.
     */
    public function bulkUpload(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('create_stock_adjustment'), 403);

        $request->validate([
            'upload_file' => 'required|file|mimes:csv,txt|max:5120',
            'default_reason' => 'nullable|string|max:150',
        ]);

        set_time_limit(600);

        $filePath = $request->file('upload_file')->getRealPath();
        $defaultReason = trim($request->input('default_reason') ?: 'Bulk stock upload');

        // Detect delimiter (Excel may save with ; or tabs depending on locale)
        $firstLine = file_get_contents($filePath, false, null, 0, 1024);
        $delimiter = ',';
        if (strpos($firstLine, "\t") !== false) {
            $delimiter = "\t";
        } elseif (strpos($firstLine, ';') !== false && strpos($firstLine, ',') === false) {
            $delimiter = ';';
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 0, $delimiter);

        $headerMap = [];
        foreach ((array) $header as $columnIndex => $columnName) {
            $normalized = strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $columnName)));
            $normalized = trim(preg_replace('/[^a-z0-9]+/', '_', $normalized), '_');

            if (in_array($normalized, ['sku', 'product_sku', 'stock_keeping_unit'])) {
                $headerMap['sku'] = $columnIndex;
            } elseif (in_array($normalized, ['product_name', 'name', 'product'])) {
                $headerMap['name'] = $columnIndex;
            } elseif (str_starts_with($normalized, 'quantity') || in_array($normalized, ['qty', 'qty_to_add', 'add_quantity'])) {
                $headerMap['quantity'] = $columnIndex;
            } elseif (str_contains($normalized, 'purchase_price')) {
                $headerMap['purchase_price'] = $columnIndex;
            } elseif (str_contains($normalized, 'selling_price')) {
                $headerMap['selling_price'] = $columnIndex;
            } elseif (str_contains($normalized, 'expiry')) {
                $headerMap['expiry_date'] = $columnIndex;
            } elseif ($normalized === 'reason') {
                $headerMap['reason'] = $columnIndex;
            }
        }

        if (!isset($headerMap['quantity']) || (!isset($headerMap['sku']) && !isset($headerMap['name']))) {
            fclose($handle);
            return back()->with('error', 'Invalid file: it must contain a "Quantity to Add" column and a "SKU" or "Product Name" column. Please use the downloaded template.');
        }

        // Lookup maps for existing products (case-insensitive)
        $productsBySku = [];
        $productsByName = [];
        foreach (Product::select(['id', 'sku', 'name'])->get() as $product) {
            if ($product->sku !== null && $product->sku !== '') {
                $productsBySku[strtolower(trim($product->sku))] = $product->id;
            }
            $productsByName[strtolower(trim($product->name))] = $product->id;
        }

        $cell = fn (array $row, string $key) => isset($headerMap[$key]) ? trim((string) ($row[$headerMap[$key]] ?? '')) : '';

        $validRows = [];
        $seenProductIds = [];
        $skipCount = 0;
        $errors = [];
        $rowNumber = 1;

        // Pass 1: parse and validate every row before touching stock
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            $sku = $cell($row, 'sku');
            $name = $cell($row, 'name');
            $quantityRaw = $cell($row, 'quantity');

            // Rows without a quantity are simply products the user did not restock
            if ($quantityRaw === '' || ($sku === '' && $name === '')) {
                continue;
            }

            $label = $name !== '' ? $name : $sku;
            $quantity = $this->parseNumber($quantityRaw);

            if ($quantity === null || $quantity < 0) {
                $errors[] = "Row $rowNumber ($label): invalid quantity \"$quantityRaw\".";
                continue;
            }
            if ($quantity == 0) {
                continue;
            }

            $productId = null;
            if ($sku !== '') {
                $productId = $productsBySku[strtolower($sku)] ?? null;
            }
            if (!$productId && $name !== '') {
                $productId = $productsByName[strtolower($name)] ?? null;
            }
            if (!$productId) {
                $errors[] = "Row $rowNumber ($label): product not found in the system. Add it under Products first.";
                continue;
            }

            if (isset($seenProductIds[$productId])) {
                $skipCount++;
                $errors[] = "Row $rowNumber ($label): duplicate of row {$seenProductIds[$productId]}, skipped.";
                continue;
            }

            $purchasePrice = $cell($row, 'purchase_price') !== '' ? $this->parseNumber($cell($row, 'purchase_price')) : null;
            $sellingPrice = $cell($row, 'selling_price') !== '' ? $this->parseNumber($cell($row, 'selling_price')) : null;
            if (($purchasePrice !== null && $purchasePrice < 0) || ($sellingPrice !== null && $sellingPrice < 0)
                || ($cell($row, 'purchase_price') !== '' && $purchasePrice === null)
                || ($cell($row, 'selling_price') !== '' && $sellingPrice === null)) {
                $errors[] = "Row $rowNumber ($label): invalid price.";
                continue;
            }

            $expiryDate = null;
            if ($cell($row, 'expiry_date') !== '') {
                $expiryDate = $this->parseDate($cell($row, 'expiry_date'));
                if (!$expiryDate) {
                    $errors[] = "Row $rowNumber ($label): invalid expiry date \"{$cell($row, 'expiry_date')}\". Use YYYY-MM-DD.";
                    continue;
                }
            }

            $seenProductIds[$productId] = $rowNumber;
            $validRows[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'expiry_date' => $expiryDate,
                'reason' => $cell($row, 'reason') !== '' ? $cell($row, 'reason') : $defaultReason,
            ];
        }
        fclose($handle);

        if (empty($validRows)) {
            return back()
                ->with('error', 'No stock was added. Fill in "Quantity to Add" for the products you want to restock.')
                ->with('error_details', array_slice($errors, 0, 50));
        }

        // Fingerprint of the actual adjustments, so re-uploading the same data
        // (even re-saved in Excel) is detected and stock is not added twice.
        $batchRef = 'BULK-' . strtoupper(substr(sha1(json_encode($validRows)), 0, 10));
        $previousBatch = StockAdjustment::where('reason', 'like', "%[$batchRef]%")->first();
        if ($previousBatch) {
            return back()->with('error', 'This stock data was already uploaded on '
                . Carbon::parse($previousBatch->created_at)->format('M d, Y H:i')
                . '. No changes were made, to avoid adding the same stock twice.');
        }

        // Pass 2: apply all adjustments atomically
        try {
            DB::beginTransaction();

            foreach ($validRows as $data) {
                $product = Product::lockForUpdate()->findOrFail($data['product_id']);
                $previousQuantity = $product->current_stock ?? 0;
                $previousSellingPrice = $product->selling_price ?? 0;
                $newQuantity = $previousQuantity + $data['quantity'];

                $product->current_stock = $newQuantity;
                $product->stock = $newQuantity;
                if ($data['purchase_price'] !== null) {
                    $product->purchase_price = $data['purchase_price'];
                }
                if ($data['selling_price'] !== null) {
                    $product->selling_price = $data['selling_price'];
                }
                if ($data['expiry_date']) {
                    $product->expiry_date = $data['expiry_date'];
                }
                $product->save();

                StockAdjustment::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'adjustment_type' => 'add',
                    'quantity' => $data['quantity'],
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'previous_price' => $previousSellingPrice,
                    'new_price' => $data['selling_price'] ?? $previousSellingPrice,
                    'new_expiry_date' => $data['expiry_date'],
                    'reason' => mb_substr($data['reason'], 0, 200) . " [$batchRef]",
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Bulk upload failed, no stock was changed: ' . $e->getMessage());
        }

        $message = 'Bulk upload complete (' . $batchRef . '): stock added to ' . count($validRows) . ' product(s)'
            . ($skipCount ? ", $skipCount duplicate row(s) skipped" : '')
            . (count($errors) - $skipCount > 0 ? ', ' . (count($errors) - $skipCount) . ' row(s) with errors' : '') . '.';

        return redirect()->route('stock-adjustments.index')
            ->with('success', $message)
            ->with('error_details', array_slice($errors, 0, 50));
    }

    /**
     * Parse a number that may contain thousands separators (e.g. "1,500").
     */
    private function parseNumber(string $value): ?float
    {
        $value = str_replace([',', ' '], '', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Parse a date in the common formats Excel produces.
     */
    private function parseDate(string $value): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'Y/m/d', 'd.m.Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
