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

echo "🛒 ADDING TEST PRODUCTS TO CLEAN SYSTEM\n\n";

try {
    // Create basic data first
    echo "📁 Creating basic categories, brands, suppliers...\n";
    
    $category = Category::create(['name' => 'Medicine']);
    $brand = Brand::create(['name' => 'Test Brand']);
    $supplier = Contact::create(['name' => 'Test Supplier', 'type' => 'supplier']);
    
    echo "✅ Created basic data\n";
    
    // Create products without unit_id constraint
    echo "🛍️ Creating test products...\n";
    
    $products = [
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

    // Insert products using raw SQL to bypass unit_id issue
    foreach ($products as $index => $product) {
        $sql = "INSERT INTO products (name, sku, category_id, brand_id, supplier_id, unit_id, purchase_price, selling_price, current_stock, product_type, tax, expiry_date, alert_quantity, description, business_location, last_purchase_price, last_purchase_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        DB::insert($sql, [
            $product['name'],
            $product['sku'],
            $product['category_id'],
            $product['brand_id'],
            $product['supplier_id'],
            $product['purchase_price'],
            $product['selling_price'],
            $product['current_stock'],
            $product['product_type'],
            $product['tax'],
            $product['expiry_date'],
            $product['alert_quantity'],
            $product['description'],
            $product['business_location'],
            $product['last_purchase_price'],
            $product['last_purchase_date'],
        ]);
        
        echo "✅ Created product " . ($index + 1) . ": {$product['name']} (Stock: {$product['current_stock']})\n";
    }

    echo "\n🎉 TEST PRODUCTS CREATED SUCCESSFULLY!\n";
    echo "\n📊 PRODUCTS SUMMARY:\n";
    echo "┌─────────────────────────────────────────┐\n";
    echo "│ Product        │ Stock │ Cost   │ Price  │\n";
    echo "├─────────────────────────────────────────┤\n";
    echo "│ Paracetamol    │   3   │ 200.00 │ 250.00 │\n";
    echo "│ Ibuprofen      │   3   │ 150.00 │ 200.00 │\n";
    echo "│ Amoxicillin    │   3   │ 300.00 │ 400.00 │\n";
    echo "└─────────────────────────────────────────┘\n";
    echo "\n💰 FINANCIAL SUMMARY:\n";
    echo "Total Units: 9\n";
    echo "Total Cost: MWK 650.00\n";
    echo "Total Value: MWK 850.00\n";
    echo "Expected Profit: MWK 200.00\n";
    echo "\n🚀 SYSTEM READY FOR TESTING!\n";
    echo "\n📋 NEXT STEPS:\n";
    echo "1. Open register (MWK 0.00 opening cash)\n";
    echo "2. Add products to cart and test sales\n";
    echo "3. Verify stock updates and profit calculations\n";
    echo "4. Test stock adjustments and reports\n";
    echo "5. Check expiry tracking functionality\n";
    echo "\n🎯 All systems are ready for comprehensive testing!\n";

} catch (\Exception $e) {
    echo "❌ Error creating test products: " . $e->getMessage() . "\n";
}
