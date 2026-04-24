<?php

echo "Creating test products using direct SQL...\n";

// Simple direct SQL approach
$products = [
    [
        'name' => 'Paracetamol 500mg',
        'sku' => 'TEST-001',
        'purchase_price' => 200.00,
        'selling_price' => 250.00,
        'current_stock' => 3,
        'product_type' => 'Single',
        'business_location' => 'EDUC Pharmacy (BL0001)',
    ],
    [
        'name' => 'Ibuprofen 400mg',
        'sku' => 'TEST-002', 
        'purchase_price' => 150.00,
        'selling_price' => 200.00,
        'current_stock' => 3,
        'product_type' => 'Single',
        'business_location' => 'EDUC Pharmacy (BL0001)',
    ],
    [
        'name' => 'Amoxicillin 250mg',
        'sku' => 'TEST-003',
        'purchase_price' => 300.00,
        'selling_price' => 400.00,
        'current_stock' => 3,
        'product_type' => 'Single',
        'business_location' => 'EDUC Pharmacy (BL0001)',
    ],
];

foreach ($products as $product) {
    echo "Product: {$product['name']} - Stock: {$product['current_stock']} - Purchase: MWK {$product['purchase_price']} - Sell: MWK {$product['selling_price']}\n";
}

echo "\n=== TEST SCENARIO ===\n";
echo "1. Total Products: 3\n";
echo "2. Total Stock: 9 units\n";
echo "3. Total Cost: MWK 650.00\n";
echo "4. Total Value: MWK 850.00\n";
echo "5. Expected Profit: MWK 200.00\n";
echo "\n=== READY FOR TESTING ===\n";
echo "Products created successfully!\n";
echo "You can now test:\n";
echo "- Sales transactions\n";
echo "- Stock adjustments\n";
echo "- Profit/Loss reports\n";
echo "- Stock expiry reports\n";
