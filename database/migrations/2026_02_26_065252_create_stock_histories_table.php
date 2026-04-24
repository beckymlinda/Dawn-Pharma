<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('type', ['Purchase', 'Sale', 'Opening Stock', 'Stock Adjustment', 'Stock Transfer']);
            $table->decimal('quantity_change', 15, 2);
            $table->decimal('new_quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->date('date');
            $table->string('reference_no')->nullable();
            $table->string('customer_supplier')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('business_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
