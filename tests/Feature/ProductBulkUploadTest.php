<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class ProductBulkUploadTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private const HEADER = "Name,SKU,Description,Purchase Price,Selling Price,Current Stock,Alert Quantity,Expiry Date (YYYY-MM-DD),Category Name,Brand Name,Unit Name,Supplier Name\n";

    private function manager()
    {
        return $this->userWithPermissions(['add_product', 'view_product'], 'manager');
    }

    private function upload(string $csv, $user = null, string $name = 'products.csv')
    {
        $file = UploadedFile::fake()->createWithContent($name, $csv);

        return $this->actingAs($user ?? $this->manager())->post('/products/bulk-upload', ['upload_file' => $file]);
    }

    public function test_template_downloads_and_uploads_as_is(): void
    {
        $user = $this->manager();
        $template = $this->actingAs($user)->get('/products/download-template')->assertOk()->streamedContent();

        $this->upload($template, $user)->assertSessionHas('success', fn ($m) => str_contains($m, '1 created'));

        $p = Product::with(['category', 'brand', 'unit', 'supplier'])->where('sku', 'PRD-00001')->firstOrFail();
        $this->assertSame('Paracetamol 500mg', $p->name);
        $this->assertEquals(1500, $p->purchase_price);
        $this->assertEquals(2500, $p->selling_price);
        $this->assertEquals(100, $p->current_stock);
        $this->assertSame('2027-12-31', substr((string) $p->expiry_date, 0, 10));
        $this->assertSame('Pain Killers', $p->category->name);
        $this->assertSame('Generic', $p->brand->name);
        $this->assertSame('Pack', $p->unit->name);
        $this->assertSame('Main Distributor', $p->supplier->name);
        $this->assertSame(1, StockHistory::where('product_id', $p->id)->where('type', 'Opening Stock')->count());
    }

    public function test_excel_csv_with_bom_and_columns_in_another_order(): void
    {
        $csv = "\xEF\xBB\xBFSelling Price,Name,Current Stock,Purchase Price\n3000,Amoxicillin 250mg,40,1800\n";

        $this->upload($csv)->assertSessionHas('success', fn ($m) => str_contains($m, '1 created'));

        $p = Product::where('name', 'Amoxicillin 250mg')->firstOrFail();
        $this->assertEquals(3000, $p->selling_price);
        $this->assertEquals(1800, $p->purchase_price);
        $this->assertEquals(40, $p->current_stock);
    }

    public function test_prices_with_thousand_separators_and_currency(): void
    {
        $csv = self::HEADER . "Insulin Pen,INS-1,,\"12,500\",\"MWK 18,000.50\",\"1,200\",5,,,,,\n";

        $this->upload($csv);

        $p = Product::where('sku', 'INS-1')->firstOrFail();
        $this->assertEquals(12500, $p->purchase_price);
        $this->assertEquals(18000.50, $p->selling_price);
        $this->assertEquals(1200, $p->current_stock);
        $this->assertEquals(12500, $p->last_purchase_price);
    }

    public function test_malawian_day_first_dates(): void
    {
        $csv = self::HEADER
            . "Drug A,DA,,100,200,1,1,31/12/2027,,,,\n"
            . "Drug B,DB,,100,200,1,1,05/06/2027,,,,\n"
            . "Drug C,DC,,100,200,1,1,2027-03-15,,,,\n";

        $this->upload($csv);

        $this->assertSame('2027-12-31', substr((string) Product::where('sku', 'DA')->value('expiry_date'), 0, 10));
        $this->assertSame('2027-06-05', substr((string) Product::where('sku', 'DB')->value('expiry_date'), 0, 10));
        $this->assertSame('2027-03-15', substr((string) Product::where('sku', 'DC')->value('expiry_date'), 0, 10));
    }

    public function test_other_date_styles_bad_values_and_old_excel_files(): void
    {
        $csv = self::HEADER
            . "Pack Date,PD,,100,200,1,1,12/2027,,,,\n"          // month/year as printed on packs
            . "Excel Date,ED,,100,200,1,1,46387,,,,\n"           // Excel date number = 2026-12-31
            . "Words Date,WD,,100,200,1,1,31 Dec 2027,,,,\n"
            . "Bad Date,BD,,100,200,1,1,soon,,,,\n"
            . "Bad Stock,BS,,100,200,ten,1,,,,,\n"
            . "Caf\xE9 Syrup,CS,,100,200,1,1,,,,,\n";            // Windows-1252 "é" from older Excel

        $this->upload($csv)->assertSessionHas('error_details', function ($errors) {
            $text = implode(' ', $errors);

            return count($errors) === 2 && str_contains($text, 'Bad Date') && str_contains($text, 'Bad Stock');
        });

        $this->assertSame('2027-12-31', substr((string) Product::where('sku', 'PD')->value('expiry_date'), 0, 10));
        $this->assertSame('2026-12-31', substr((string) Product::where('sku', 'ED')->value('expiry_date'), 0, 10));
        $this->assertSame('2027-12-31', substr((string) Product::where('sku', 'WD')->value('expiry_date'), 0, 10));
        $this->assertSame('Café Syrup', Product::where('sku', 'CS')->value('name'));
        $this->assertNull(Product::where('sku', 'BD')->first());
    }

    public function test_duplicates_are_skipped_and_missing_skus_generated(): void
    {
        Product::create(['name' => 'Existing', 'sku' => 'EX-1', 'business_location' => 'Main', 'purchase_price' => 1, 'selling_price' => 2, 'current_stock' => 0, 'product_type' => 'Single']);
        $csv = self::HEADER
            . "Existing again,EX-1,,100,200,1,1,,,,,\n"   // SKU already in the shop
            . "New One,,,100,200,1,1,,,,,\n"              // no SKU
            . "New One,,,100,200,1,1,,,,,\n"              // same name twice in the file
            . "New Two,,,100,200,1,1,,,,,\n";

        $this->upload($csv)->assertSessionHas('success', fn ($m) => str_contains($m, '2 created') && str_contains($m, '2 skipped'));

        $skus = Product::whereIn('name', ['New One', 'New Two'])->pluck('sku');
        $this->assertCount(2, $skus);
        $this->assertCount(2, $skus->unique());
        $this->assertTrue($skus->every(fn ($s) => !empty($s)));
    }

    public function test_semicolon_file_with_commas_in_text(): void
    {
        $csv = "Name;SKU;Description;Purchase Price;Selling Price;Current Stock\nZinc Tablets;ZN-1;Take 1, twice daily;300;500;60\n";

        $this->upload($csv);

        $p = Product::where('sku', 'ZN-1')->firstOrFail();
        $this->assertSame('Take 1, twice daily', $p->description);
        $this->assertEquals(500, $p->selling_price);
    }

    public function test_row_without_selling_price_is_reported_not_sold_for_free(): void
    {
        $csv = self::HEADER . "No Price,NP-1,,100,,5,1,,,,,\nGood,GD-1,,100,150,5,1,,,,,\n";

        $this->upload($csv)->assertSessionHas('error_details', fn ($e) => str_contains(implode(' ', $e), 'No Price'));

        $this->assertNull(Product::where('sku', 'NP-1')->first());
        $this->assertNotNull(Product::where('sku', 'GD-1')->first());
    }

    public function test_categories_match_whatever_the_case(): void
    {
        Category::create(['name' => 'Antibiotics']);
        $csv = self::HEADER . "Cipro,CP-1,,100,200,1,1,,antibiotics,,,\nDoxy,DX-1,,100,200,1,1,,ANTIBIOTICS ,,,\n";

        $this->upload($csv);

        $this->assertSame(1, Category::count());
        $this->assertSame(2, Product::where('category_id', Category::first()->id)->count());
    }

    public function test_another_shops_sku_does_not_block_the_upload(): void
    {
        $other = Tenant::create(['name' => 'Other Shop', 'slug' => 'other-shop']);
        with_tenant($other, fn () => Product::create(['name' => 'Theirs', 'sku' => 'SAME-1', 'business_location' => 'X', 'purchase_price' => 1, 'selling_price' => 2, 'current_stock' => 0, 'product_type' => 'Single']));

        $this->upload(self::HEADER . "Ours,SAME-1,,100,200,1,1,,,,,\n")->assertSessionHas('success', fn ($m) => str_contains($m, '1 created'));

        $this->assertSame('Ours', Product::where('sku', 'SAME-1')->value('name'));
    }

    public function test_large_file(): void
    {
        $csv = self::HEADER;
        for ($i = 1; $i <= 1500; $i++) {
            $csv .= "Product $i,,,100,200,10,1,2028-01-01,Cat " . ($i % 10) . ",Brand " . ($i % 5) . ",Pack,Supplier " . ($i % 3) . "\n";
        }

        $start = microtime(true);
        $this->upload($csv)->assertSessionHas('success', fn ($m) => str_contains($m, '1500 created'));
        $this->assertLessThan(60, microtime(true) - $start);
        $this->assertSame(1500, Product::count());
        $this->assertSame(10, Category::count());
    }

    public function test_wrong_file_type_and_permissions(): void
    {
        $this->upload('not really', null, 'products.xlsx')->assertSessionHasErrors('upload_file');

        $cashier = $this->userWithPermissions(['add_pos_sell'], 'cashier');
        $this->upload(self::HEADER . "X,X1,,1,2,1,1,,,,,\n", $cashier)->assertForbidden();
        $this->assertSame(0, Product::count());
    }
}
