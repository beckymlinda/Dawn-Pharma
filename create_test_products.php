<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

echo "Creating test products for system verification...\n\n";

try {
    DB::beginTransaction();

    // Get or create test data (handle unit_id properly)
    $category = Category::firstOrCreate(['name' => 'Medicine'], ['name' => 'Medicine']);
    $brand = Brand::firstOrCreate(['name' => 'Test Brand'], ['name' => 'Test Brand']);
    $supplier = Contact::firstOrCreate(['name' => 'Test Supplier', 'type' => 'supplier'], ['name' => 'Test Supplier', 'type' => 'supplier']);

    // Create 3 test products with quantity 3 each
    $testProducts = [
        [
            'name' => 'Paracetamol 500mg',
            'sku' => 'TEST-001',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 200.00,
            'selling_price' => 250.00,
            'current_stock' => 3,
            'product_type' => 'Single',
            'tax' => 0,
            'expiry_date' => now()->addDays(365)->format('Y-m-d'),
            'alert_quantity' => 1,
            'description' => 'Test product for system verification',
            'business_location' => 'EDUC Pharmacy (BL0001)',
            'last_purchase_price' => 200.00,
            'last_purchase_date' => now()->format('Y-m-d'),
        ],
        [
            'name' => 'Ibuprofen 400mg',
            'sku' => 'TEST-002',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 150.00,
            'selling_price' => 200.00,
            'current_stock' => 3,
            'product_type' => 'Single',
            'tax' => 0,
            'expiry_date' => now()->addDays(180)->format('Y-m-d'),
            'alert_quantity' => 2,
            'description' => 'Test product for system verification',
            'business_location' => 'EDUC Pharmacy (BL0001)',
            'last_purchase_price' => 150.00,
            'last_purchase_date' => now()->format('Y-m-d'),
        ],
        [
            'name' => 'Amoxicillin 250mg',
            'sku' => 'TEST-003',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 300.00,
            'selling_price' => 400.00,
            'current_stock' => 3,
            'product_type' => 'Single',
            'tax' => 0,
            'expiry_date' => now()->addDays(730)->format('Y-m-d'),
            'alert_quantity' => 1,
            'description' => 'Test product for system verification',
            'business_location' => 'EDUC Pharmacy (BL0001)',
            'last_purchase_price' => 300.00,
            'last_purchase_date' => now()->format('Y-m-d'),
        ],
    ];

    foreach ($testProducts as $productData) {
        // Check if product already exists
        $existingProduct = Product::where('sku', $productData['sku'])->first();
        if (!$existingProduct) {
            // Create without unit_id first, then update it
            $product = Product::create([
                'name' => $productData['name'],
                'sku' => $productData['sku'],
                'category_id' => $productData['category_id'],
                'brand_id' => $productData['brand_id'],
                'supplier_id' => $productData['supplier_id'],
                'purchase_price' => $productData['purchase_price'],
                'selling_price' => $productData['selling_price'],
                'current_stock' => $productData['current_stock'],
                'product_type' => $productData['product_type'],
                'tax' => $productData['tax'],
                'expiry_date' => $productData['expiry_date'],
                'alert_quantity' => $productData['alert_quantity'],
                'description' => $productData['description'],
                'business_location' => $productData['business_location'],
                'last_purchase_price' => $productData['last_purchase_price'],
                'last_purchase_date' => $productData['last_purchase_date'],
            ]);
            
            // Set unit_id to 1 (assuming 'Pieces' unit exists)
            $product->update(['unit_id' => 1]);
            
            echo "✅ Created product: {$productData['name']} (SKU: {$productData['sku']})\n";
        } else {
            echo "ℹ Product already exists: {$productData['name']} (SKU: {$productData['sku']})\n";
        }
    }

    DB::commit();

    echo "\n=== TEST PRODUCTS CREATED ===\n";
    echo "Products available for testing:\n";
    echo "1. Paracetamol 500mg - 3 units @ MWK 250.00 each\n";
    echo "2. Ibuprofen 400mg - 3 units @ MWK 150.00 each\n"; 
    echo "3. Amoxicillin 250mg - 3 units @ MWK 300.00 each\n";
    echo "\n=== EXPECTED RESULTS ===\n";
    echo "Total Stock Value: 9 units × Average Cost = MWK 650.00\n";
    echo "Total Sale Value: 9 units × Average Price = MWK 850.00\n";
    echo "Expected Profit: MWK 200.00 (if all sold at current prices)\n";
    echo "\n=== NEXT STEPS ===\n";
    echo "1. Open register and test sales\n";
    echo "2. Check profit/loss report\n";
    echo "3. Test stock adjustments\n";
    echo "4. Verify stock expiry reports\n";
    echo "\nReady for system testing!\n";

} catch (\Exception $e) {
    echo "❌ Error creating test products: " . $e->getMessage() . "\n";
    DB::rollBack();
}
