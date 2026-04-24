<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('brand_id')->constrained('contacts')->onDelete('set null');
            $table->date('expiry_date')->nullable()->after('alert_quantity');
            $table->decimal('last_purchase_price', 15, 2)->nullable()->after('purchase_price');
            $table->date('last_purchase_date')->nullable()->after('expiry_date');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'expiry_date', 'last_purchase_price', 'last_purchase_date']);
        });
    }
};
