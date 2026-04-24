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
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['brand_id']);
            
            // Modify existing columns to be nullable
            $table->unsignedBigInteger('unit_id')->nullable()->change();
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->unsignedBigInteger('brand_id')->nullable()->change();
            
            // Re-add foreign key constraints with set null
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['brand_id']);
            
            // Modify columns to be not nullable again
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->unsignedBigInteger('brand_id')->nullable(false)->change();
            
            // Re-add foreign key constraints with cascade
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
        });
    }
};
