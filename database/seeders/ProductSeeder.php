<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed sample pharmacy products along with the units, categories,
     * brands and supplier they reference. Safe to run more than once:
     * existing records (matched by name / SKU) are left untouched.
     */
    public function run(): void
    {
        $units = [];
        foreach ([
            ['name' => 'Tablet', 'short_name' => 'tab', 'allow_decimal' => false],
            ['name' => 'Capsule', 'short_name' => 'cap', 'allow_decimal' => false],
            ['name' => 'Bottle', 'short_name' => 'btl', 'allow_decimal' => false],
            ['name' => 'Pack', 'short_name' => 'pk', 'allow_decimal' => false],
            ['name' => 'Tube', 'short_name' => 'tube', 'allow_decimal' => false],
            ['name' => 'Piece', 'short_name' => 'pc', 'allow_decimal' => false],
        ] as $unit) {
            $units[$unit['name']] = Unit::firstOrCreate(['name' => $unit['name']], $unit)->id;
        }

        $categories = [];
        foreach ([
            'Pain Killers', 'Antibiotics', 'Vitamins & Supplements', 'Antimalarials',
            'Cough & Cold', 'Digestive Health', 'Skin Care', 'First Aid',
        ] as $name) {
            $categories[$name] = Category::firstOrCreate(['name' => $name])->id;
        }

        $brands = [];
        foreach (['Generic', 'GSK', 'Pfizer', 'Cipla', 'Novartis', 'Dettol'] as $name) {
            $brands[$name] = Brand::firstOrCreate(['name' => $name])->id;
        }

        $supplierId = Contact::firstOrCreate(
            ['name' => 'Main Distributor', 'type' => 'supplier'],
            ['phone' => '+265 999 000 000', 'city' => 'Lilongwe', 'country' => 'Malawi']
        )->id;

        // [sku, name, category, brand, unit, purchase price, selling price, stock, alert qty, expiry months]
        $products = [
            ['PRD-00001', 'Paracetamol 500mg Tablets', 'Pain Killers', 'Generic', 'Pack', 1500, 2500, 120, 20, 24],
            ['PRD-00002', 'Ibuprofen 400mg Tablets', 'Pain Killers', 'Generic', 'Pack', 2000, 3500, 80, 15, 30],
            ['PRD-00003', 'Diclofenac 50mg Tablets', 'Pain Killers', 'Cipla', 'Pack', 1800, 3000, 60, 10, 24],
            ['PRD-00004', 'Amoxicillin 500mg Capsules', 'Antibiotics', 'GSK', 'Pack', 3500, 6000, 50, 10, 18],
            ['PRD-00005', 'Ciprofloxacin 500mg Tablets', 'Antibiotics', 'Cipla', 'Pack', 4000, 7000, 40, 10, 18],
            ['PRD-00006', 'Metronidazole 400mg Tablets', 'Antibiotics', 'Generic', 'Pack', 2500, 4500, 45, 10, 24],
            ['PRD-00007', 'Vitamin C 1000mg Tablets', 'Vitamins & Supplements', 'Generic', 'Bottle', 5000, 8500, 70, 10, 24],
            ['PRD-00008', 'Multivitamin Syrup 200ml', 'Vitamins & Supplements', 'Pfizer', 'Bottle', 6500, 11000, 30, 5, 20],
            ['PRD-00009', 'Folic Acid 5mg Tablets', 'Vitamins & Supplements', 'Generic', 'Pack', 1200, 2000, 90, 15, 30],
            ['PRD-00010', 'Lumefantrine/Artemether (LA) Tablets', 'Antimalarials', 'Novartis', 'Pack', 4500, 7500, 100, 20, 24],
            ['PRD-00011', 'Fansidar Tablets', 'Antimalarials', 'Generic', 'Pack', 2000, 3500, 35, 10, 24],
            ['PRD-00012', 'Cough Syrup 100ml', 'Cough & Cold', 'Generic', 'Bottle', 3000, 5000, 40, 10, 18],
            ['PRD-00013', 'Cetirizine 10mg Tablets', 'Cough & Cold', 'Cipla', 'Pack', 1500, 2800, 55, 10, 30],
            ['PRD-00014', 'Oral Rehydration Salts (ORS)', 'Digestive Health', 'Generic', 'Piece', 300, 600, 200, 30, 36],
            ['PRD-00015', 'Omeprazole 20mg Capsules', 'Digestive Health', 'Generic', 'Pack', 2500, 4500, 50, 10, 24],
            ['PRD-00016', 'Hydrocortisone Cream 1%', 'Skin Care', 'GSK', 'Tube', 2800, 4800, 25, 5, 24],
            ['PRD-00017', 'Antifungal Cream (Clotrimazole)', 'Skin Care', 'Generic', 'Tube', 2200, 3800, 30, 5, 24],
            ['PRD-00018', 'Antiseptic Liquid 500ml', 'First Aid', 'Dettol', 'Bottle', 6000, 9500, 20, 5, 36],
            ['PRD-00019', 'Elastic Bandage 10cm', 'First Aid', 'Generic', 'Piece', 1500, 2500, 40, 10, null],
            ['PRD-00020', 'Surgical Gloves (Box of 100)', 'First Aid', 'Generic', 'Pack', 9000, 14000, 15, 5, 48],
        ];

        $created = 0;
        foreach ($products as [$sku, $name, $category, $brand, $unit, $purchase, $selling, $stock, $alert, $expiryMonths]) {
            $product = Product::firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'business_location' => 'EDUC Pharmacy (BL0001)',
                    'unit_id' => $units[$unit],
                    'category_id' => $categories[$category],
                    'brand_id' => $brands[$brand],
                    'supplier_id' => $supplierId,
                    'purchase_price' => $purchase,
                    'last_purchase_price' => $purchase,
                    'selling_price' => $selling,
                    'current_stock' => $stock,
                    'product_type' => 'Single',
                    'alert_quantity' => $alert,
                    'expiry_date' => $expiryMonths ? now()->addMonths($expiryMonths)->toDateString() : null,
                    'last_purchase_date' => now()->toDateString(),
                ]
            );

            if ($product->wasRecentlyCreated) {
                // Keep the legacy `stock` column in sync, as the app does on adjustments
                $product->forceFill(['stock' => $stock])->save();

                StockHistory::create([
                    'product_id' => $product->id,
                    'type' => 'Opening Stock',
                    'quantity_change' => $stock,
                    'new_quantity' => $stock,
                    'unit_cost' => $purchase,
                    'date' => now(),
                    'reference_no' => 'SEED-' . now()->format('Ymd'),
                    'customer_supplier' => 'Main Distributor',
                    'business_location_id' => null,
                ]);

                $created++;
            }
        }

        $this->command->info("Seeded $created new product(s) (" . count($products) . ' in seed list).');
    }
}
