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

    public function create()
    {
        $products = Product::orderBy('name')->get();
        return view('stock-adjustments.create', compact('products'));
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
}
