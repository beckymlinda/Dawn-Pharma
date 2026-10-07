<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record which user made each sale and each stock movement.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('register_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('stock_histories', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('product_id')->constrained('users')->nullOnDelete();
        });

        // Backfill existing sales with the user who owned the register they were made on
        // (correlated subquery rather than UPDATE ... JOIN so it also runs on SQLite in tests)
        DB::table('sales')
            ->whereNull('user_id')
            ->whereNotNull('register_id')
            ->update(['user_id' => DB::raw('(SELECT registers.user_id FROM registers WHERE registers.id = sales.register_id)')]);
    }

    public function down(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
