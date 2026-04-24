<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockHistory;
use App\Models\StockAdjustment;
use App\Models\Register;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

echo "🧹 CLEANING SYSTEM - CREATING CLEAN SLATE\n\n";

try {
    echo "🔄 Starting system cleanup...\n";
    
    // Disable foreign key checks temporarily
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
    // Clear all data in correct order (child tables first)
    echo "🗑️  Clearing sales items...\n";
    SaleItem::truncate();
    
    echo "🗑️  Clearing sales...\n";
    Sale::truncate();
    
    echo "🗑️  Clearing purchase items...\n";
    PurchaseItem::truncate();
    
    echo "🗑️  Clearing purchases...\n";
    Purchase::truncate();
    
    echo "🗑️  Clearing stock history...\n";
    StockHistory::truncate();
    
    echo "🗑️  Clearing stock adjustments...\n";
    StockAdjustment::truncate();
    
    echo "🗑️  Clearing registers...\n";
    Register::truncate();
    
    echo "🗑️  Clearing products...\n";
    Product::truncate();
    
    echo "🗑️  Clearing categories...\n";
    Category::truncate();
    
    echo "🗑️  Clearing brands...\n";
    Brand::truncate();
    
    echo "🗑️  Clearing contacts (suppliers/customers)...\n";
    Contact::truncate();
    
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    echo "\n✅ SYSTEM CLEANED SUCCESSFULLY!\n";

    echo "\n✅ SYSTEM CLEANED SUCCESSFULLY!\n";
    echo "📊 CLEANING SUMMARY:\n";
    echo "   - Sales & Sale Items: Cleared\n";
    echo "   - Purchases & Purchase Items: Cleared\n";
    echo "   - Stock History: Cleared\n";
    echo "   - Stock Adjustments: Cleared\n";
    echo "   - Registers: Cleared\n";
    echo "   - Products: Cleared\n";
    echo "   - Categories: Cleared\n";
    echo "   - Brands: Cleared\n";
    echo "   - Contacts: Cleared\n";
    echo "\n🎯 SYSTEM IS NOW CLEAN - READY FOR FRESH START!\n";

} catch (\Exception $e) {
    echo "❌ Error cleaning system: " . $e->getMessage() . "\n";
    DB::rollBack();
    exit(1);
}

echo "\n📝 NEXT STEPS:\n";
echo "1. Run: php create_test_products.php\n";
echo "2. Open register and test sales\n";
echo "3. Verify all system components\n";
echo "4. Check reports and functionality\n";
echo "\n🚀 Ready for fresh system testing!\n";
