<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Contact;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchases = Purchase::with(['supplier', 'user'])
            ->latest()
            ->paginate(10);
        
        return view('pos.purchases', compact('purchases'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Contact::where('type', 'supplier')->get();
        $products = Product::all();
        
        return view('purchases.create', compact('suppliers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:contacts,id',
            'reference_no' => 'nullable|string|unique:purchases,reference_no',
            'purchase_date' => 'required|date',
            'status' => 'required|in:ordered,received,pending',
            'payment_status' => 'required|in:paid,due,partial',
            'products' => 'required|array|min:1',
            'products.*' => 'required|exists:products,id',
            'quantities.*' => 'required|numeric|min:0.01',
            'unit_costs.*' => 'required|numeric|min:0',
            'expiry_dates.*' => 'nullable|date',
            'discounts.*' => 'nullable|numeric|min:0|max:100',
            'taxes.*' => 'nullable|numeric|min:0|max:100',
        ]);

        // Auto-generate reference number if empty
        if (empty($validated['reference_no'])) {
            $lastPurchase = \App\Models\Purchase::orderBy('id', 'desc')->first();
            $nextId = ($lastPurchase ? $lastPurchase->id : 0) + 1;
            $validated['reference_no'] = 'PUR-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
        }

        try {
            DB::beginTransaction();

            $grandTotal = 0;
            $products = $request->input('products');
            $quantities = $request->input('quantities');
            $unitCosts = $request->input('unit_costs');
            $expiryDates = $request->input('expiry_dates');
            $discounts = $request->input('discounts');
            $taxes = $request->input('taxes');

            // Calculate grand total
            foreach ($products as $index => $productId) {
                $quantity = (float) $quantities[$index];
                $unitCost = (float) $unitCosts[$index];
                $discountPercent = (float) ($discounts[$index] ?? 0);
                $taxPercent = (float) ($taxes[$index] ?? 0);

                $subtotal = $quantity * $unitCost;
                $discountAmount = ($subtotal * $discountPercent) / 100;
                $taxableAmount = $subtotal - $discountAmount;
                $taxAmount = ($taxableAmount * $taxPercent) / 100;
                $lineTotal = $taxableAmount + $taxAmount;

                $grandTotal += $lineTotal;
            }

            // Create Purchase
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'business_location' => 'EDUC Pharmacy (BL0001)',
                'reference_no' => $validated['reference_no'],
                'purchase_date' => $validated['purchase_date'],
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
                'grand_total' => $grandTotal,
                'created_by' => auth()->id(),
            ]);

            // Create Purchase Items and update stock
            foreach ($products as $index => $productId) {
                $quantity = (float) $quantities[$index];
                $unitCost = (float) $unitCosts[$index];
                $discountPercent = (float) ($discounts[$index] ?? 0);
                $taxPercent = (float) ($taxes[$index] ?? 0);

                $subtotal = $quantity * $unitCost;
                $discountAmount = ($subtotal * $discountPercent) / 100;
                $taxableAmount = $subtotal - $discountAmount;
                $taxAmount = ($taxableAmount * $taxPercent) / 100;
                $lineTotal = $taxableAmount + $taxAmount;

                // Create Purchase Item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'expiry_date' => $expiryDates[$index] ?? null,
                    'discount_percent' => $discountPercent,
                    'tax_percent' => $taxPercent,
                    'line_total' => $lineTotal,
                ]);

                // Update Product Stock
                $product = Product::find($productId);
                $oldStock = $product->current_stock ?? 0;
                $newStock = $oldStock + $quantity;
                $product->stock += $quantity;
                $product->current_stock = $newStock;
                $product->save();

                // Log stock history
                StockHistory::create([
                    'product_id' => $productId,
                    'type' => 'Purchase',
                    'quantity_change' => $quantity,
                    'new_quantity' => $newStock,
                    'unit_cost' => $unitCost,
                    'date' => $validated['purchase_date'],
                    'reference_no' => $validated['reference_no'],
                    'customer_supplier' => $purchase->supplier->name ?? 'Unknown Supplier',
                    'business_location_id' => 1 // EDUC Pharmacy location ID
                ]);
            }

            DB::commit();

            return redirect()->route('purchases.index')
                ->with('success', 'Purchase created successfully! (Reference: ' . $purchase->reference_no . ')');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withError('Error creating purchase: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'user', 'purchaseItems.product']);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $suppliers = Contact::where('type', 'supplier')->get();
        $products = Product::all();
        $purchase->load('purchaseItems');
        
        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:contacts,id',
            'reference_no' => 'required|string|unique:purchases,reference_no,' . $purchase->id,
            'purchase_date' => 'required|date',
            'status' => 'required|in:ordered,received,pending',
            'payment_status' => 'required|in:paid,due,partial',
        ]);

        $purchase->update($validated);

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        // Reverse stock updates
        $purchase->purchaseItems()->each(function ($item) {
            $product = Product::find($item->product_id);
            $product->stock -= $item->quantity;
            $product->current_stock -= $item->quantity;
            $product->save();
        });

        $purchase->delete();

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase deleted successfully!');
    }
}
