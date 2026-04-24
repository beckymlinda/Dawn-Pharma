<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Unit;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::pluck('id')->toArray();
        $brands = Brand::pluck('id')->toArray();
        $locations = Location::pluck('id')->toArray();
        $units = Unit::pluck('id')->toArray();

        // If no categories, brands, locations, or units exist, create some basic ones
        if (empty($categories)) {
            $this->call(CategorySeeder::class);
            $categories = Category::pluck('id')->toArray();
        }

        if (empty($brands)) {
            $this->call(BrandSeeder::class);
            $brands = Brand::pluck('id')->toArray();
        }

        if (empty($locations)) {
            $this->call(LocationSeeder::class);
            $locations = Location::pluck('id')->toArray();
        }

        if (empty($units)) {
            $this->call(UnitSeeder::class);
            $units = Unit::pluck('id')->toArray();
        }

        $products = [
            [
                'name' => 'Paracetamol 500mg Tablets',
                'sku' => 'PAR001',
                'description' => 'Pain relief medication for headaches, fever, and mild pain',
                'purchase_price' => 2.50,
                'selling_price' => 4.50,
                'current_stock' => 150,
                'business_location' => $this->getLocationId($locations, 0),
                'unit_id' => $this->getUnitId($units, 0),
                'category_id' => $this->getCategoryId($categories, 0),
                'brand_id' => $this->getBrandId($brands, 0),
                'expiry_date' => now()->addMonths(24),
            ],
            [
                'name' => 'Amoxicillin 250mg Capsules',
                'sku' => 'AMX001',
                'description' => 'Antibiotic for bacterial infections',
                'purchase_price' => 8.75,
                'selling_price' => 15.50,
                'current_stock' => 75,
                'category_id' => $this->getCategoryId($categories, 0),
                'brand_id' => $this->getBrandId($brands, 1),
                'expiry_date' => now()->addMonths(18),
            ],
            [
                'name' => 'Ibuprofen 400mg Tablets',
                'sku' => 'IBU001',
                'description' => 'Anti-inflammatory pain medication',
                'purchase_price' => 3.25,
                'selling_price' => 6.75,
                'current_stock' => 200,
                'category_id' => $this->getCategoryId($categories, 0),
                'brand_id' => $this->getBrandId($brands, 2),
                'expiry_date' => now()->addMonths(30),
            ],
            [
                'name' => 'Vitamin C 1000mg Tablets',
                'sku' => 'VIT001',
                'description' => 'Immune system support supplement',
                'purchase_price' => 4.20,
                'selling_price' => 8.50,
                'current_stock' => 120,
                'category_id' => $this->getCategoryId($categories, 1),
                'brand_id' => $this->getBrandId($brands, 3),
                'expiry_date' => now()->addMonths(24),
            ],
            [
                'name' => 'Multivitamin Complex',
                'sku' => 'VIT002',
                'description' => 'Daily multivitamin supplement',
                'purchase_price' => 6.80,
                'selling_price' => 12.50,
                'current_stock' => 90,
                'category_id' => $this->getCategoryId($categories, 1),
                'brand_id' => $this->getBrandId($brands, 4),
                'expiry_date' => now()->addMonths(20),
            ],
            [
                'name' => 'Hand Sanitizer 500ml',
                'sku' => 'SAN001',
                'description' => 'Alcohol-based hand sanitizer',
                'purchase_price' => 5.50,
                'selling_price' => 9.99,
                'current_stock' => 300,
                'category_id' => $this->getCategoryId($categories, 2),
                'brand_id' => $this->getBrandId($brands, 5),
                'expiry_date' => now()->addMonths(12),
            ],
            [
                'name' => 'Face Masks 50pcs',
                'sku' => 'MAS001',
                'description' => 'Disposable surgical face masks',
                'purchase_price' => 12.00,
                'selling_price' => 18.50,
                'current_stock' => 150,
                'category_id' => $this->getCategoryId($categories, 2),
                'brand_id' => $this->getBrandId($brands, 6),
                'expiry_date' => now()->addMonths(36),
            ],
            [
                'name' => 'Digital Thermometer',
                'sku' => 'DEV001',
                'description' => 'Digital clinical thermometer',
                'purchase_price' => 15.00,
                'selling_price' => 25.00,
                'current_stock' => 45,
                'category_id' => $this->getCategoryId($categories, 3),
                'brand_id' => $this->getBrandId($brands, 7),
                'expiry_date' => null,
            ],
            [
                'name' => 'Blood Pressure Monitor',
                'sku' => 'DEV002',
                'description' => 'Digital blood pressure monitoring device',
                'purchase_price' => 45.00,
                'selling_price' => 75.00,
                'current_stock' => 25,
                'category_id' => $this->getCategoryId($categories, 3),
                'brand_id' => $this->getBrandId($brands, 8),
                'expiry_date' => null,
            ],
            [
                'name' => 'Cough Syrup 200ml',
                'sku' => 'COU001',
                'description' => 'Relief for dry cough and throat irritation',
                'purchase_price' => 7.25,
                'selling_price' => 12.50,
                'current_stock' => 80,
                'category_id' => $this->getCategoryId($categories, 0),
                'brand_id' => $this->getBrandId($brands, 9),
                'expiry_date' => now()->addMonths(15),
            ],
            [
                'name' => 'Antiseptic Solution 250ml',
                'sku' => 'ANT001',
                'description' => 'Antiseptic solution for wound cleaning',
                'purchase_price' => 6.50,
                'selling_price' => 11.25,
                'current_stock' => 110,
                'category_id' => $this->getCategoryId($categories, 2),
                'brand_id' => $this->getBrandId($brands, 10),
                'expiry_date' => now()->addMonths(24),
            ],
            [
                'name' => 'Bandage Pack 20pcs',
                'sku' => 'BAN001',
                'description' => 'Assorted bandage sizes for wound care',
                'purchase_price' => 8.00,
                'selling_price' => 14.50,
                'current_stock' => 95,
                'category_id' => $this->getCategoryId($categories, 2),
                'brand_id' => $this->getBrandId($brands, 11),
                'expiry_date' => now()->addMonths(48),
            ],
            [
                'name' => 'Eye Drops 15ml',
                'sku' => 'EYE001',
                'description' => 'Soothing eye drops for dry eyes',
                'purchase_price' => 9.50,
                'selling_price' => 16.75,
                'current_stock' => 60,
                'category_id' => $this->getCategoryId($categories, 0),
                'brand_id' => $this->getBrandId($brands, 12),
                'expiry_date' => now()->addMonths(18),
            ],
            [
                'name' => 'Omega-3 Fish Oil',
                'sku' => 'SUP001',
                'description' => 'Omega-3 fatty acid supplement',
                'purchase_price' => 12.00,
                'selling_price' => 22.50,
                'current_stock' => 70,
                'category_id' => $this->getCategoryId($categories, 1),
                'brand_id' => $this->getBrandId($brands, 13),
                'expiry_date' => now()->addMonths(24),
            ],
            [
                'name' => 'Probiotic Capsules',
                'sku' => 'SUP002',
                'description' => 'Digestive health probiotic supplement',
                'purchase_price' => 15.50,
                'selling_price' => 28.00,
                'current_stock' => 55,
                'category_id' => $this->getCategoryId($categories, 1),
                'brand_id' => $this->getBrandId($brands, 14),
                'expiry_date' => now()->addMonths(12),
            ],
            [
                'name' => 'Allergy Tablets 10mg',
                'sku' => 'ALL001',
                'description' => 'Antihistamine for allergy relief',
                'purchase_price' => 5.75,
                'selling_price' => 10.50,
                'current_stock' => 130,
                'category_id' => $this->getCategoryId($categories, 0),
                'brand_id' => $this->getBrandId($brands, 15),
                'expiry_date' => now()->addMonths(30),
            ],
            [
                'name' => 'Pregnancy Test Kit',
                'sku' => 'TST001',
                'description' => 'Home pregnancy test kit',
                'purchase_price' => 3.50,
                'selling_price' => 7.99,
                'current_stock' => 85,
                'category_id' => $this->getCategoryId($categories, 3),
                'brand_id' => $this->getBrandId($brands, 16),
                'expiry_date' => now()->addMonths(24),
            ],
            [
                'name' => 'Glucometer Test Strips 50pcs',
                'sku' => 'GLU001',
                'description' => 'Blood glucose test strips',
                'purchase_price' => 25.00,
                'selling_price' => 45.00,
                'current_stock' => 40,
                'category_id' => $this->getCategoryId($categories, 3),
                'brand_id' => $this->getBrandId($brands, 17),
                'expiry_date' => now()->addMonths(18),
            ],
            [
                'name' => 'Vitamin D3 1000IU',
                'sku' => 'VIT003',
                'description' => 'Vitamin D3 supplement for bone health',
                'purchase_price' => 4.80,
                'selling_price' => 9.50,
                'current_stock' => 100,
                'category_id' => $this->getCategoryId($categories, 1),
                'brand_id' => $this->getBrandId($brands, 18),
                'expiry_date' => now()->addMonths(24),
            ],
            [
                'name' => 'Antacid Tablets 100pcs',
                'sku' => 'ANT002',
                'description' => 'Fast relief for heartburn and indigestion',
                'purchase_price' => 6.25,
                'selling_price' => 11.75,
                'current_stock' => 140,
                'category_id' => $this->getCategoryId($categories, 0),
                'brand_id' => $this->getBrandId($brands, 19),
                'expiry_date' => now()->addMonths(36),
            ],
            [
                'name' => 'First Aid Kit',
                'sku' => 'FAK001',
                'description' => 'Complete first aid kit for home use',
                'purchase_price' => 35.00,
                'selling_price' => 55.00,
                'current_stock' => 30,
                'category_id' => $this->getCategoryId($categories, 2),
                'brand_id' => $this->getBrandId($brands, 20),
                'expiry_date' => null,
            ],
            [
                'name' => 'Insulin Syringes 30pcs',
                'sku' => 'INS001',
                'description' => 'Sterile insulin syringes',
                'purchase_price' => 18.00,
                'selling_price' => 32.50,
                'current_stock' => 50,
                'category_id' => $this->getCategoryId($categories, 3),
                'brand_id' => $this->getBrandId($brands, 21),
                'expiry_date' => now()->addMonths(24),
            ],
            [
                'name' => 'Calcium Carbonate 600mg',
                'sku' => 'CAL001',
                'description' => 'Calcium supplement for bone health',
                'purchase_price' => 3.75,
                'selling_price' => 7.25,
                'current_stock' => 165,
                'category_id' => $this->getCategoryId($categories, 1),
                'brand_id' => $this->getBrandId($brands, 22),
                'expiry_date' => now()->addMonths(30),
            ],
            [
                'name' => 'Nasal Spray 15ml',
                'sku' => 'NAS001',
                'description' => 'Nasal decongestant spray',
                'purchase_price' => 8.50,
                'selling_price' => 15.00,
                'current_stock' => 75,
                'category_id' => $this->getCategoryId($categories, 0),
                'brand_id' => $this->getBrandId($brands, 23),
                'expiry_date' => now()->addMonths(20),
            ],
        ];

        foreach ($products as $index => $product) {
            Product::create([
                'name' => $product['name'],
                'sku' => $product['sku'],
                'description' => $product['description'],
                'purchase_price' => $product['purchase_price'],
                'selling_price' => $product['selling_price'],
                'current_stock' => $product['current_stock'],
                'business_location' => $this->getLocationId($locations, $index),
                'unit_id' => $this->getUnitId($units, $index),
                'category_id' => $product['category_id'],
                'brand_id' => $product['brand_id'],
                'expiry_date' => $product['expiry_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Created ' . count($products) . ' products successfully!');
    }

    private function getCategoryId(array $categories, int $index)
    {
        if (empty($categories)) {
            return 1; // Default fallback
        }
        return $categories[$index % count($categories)];
    }

    private function getBrandId(array $brands, int $index)
    {
        if (empty($brands)) {
            return 1; // Default fallback
        }
        return $brands[$index % count($brands)];
    }

    private function getLocationId(array $locations, int $index)
    {
        if (empty($locations)) {
            return 1; // Default fallback
        }
        return $locations[$index % count($locations)];
    }

    private function getUnitId(array $units, int $index)
    {
        if (empty($units)) {
            return 1; // Default fallback
        }
        return $units[$index % count($units)];
    }
}
