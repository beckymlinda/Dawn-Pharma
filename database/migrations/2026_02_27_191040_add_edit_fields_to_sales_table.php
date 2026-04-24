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
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_edited')->default(false)->after('total_items');
            $table->json('edit_log')->nullable()->after('is_edited');
            $table->timestamp('edited_at')->nullable()->after('edit_log');
            $table->unsignedBigInteger('edited_by')->nullable()->after('edited_at');
            
            $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['edited_by']);
            $table->dropColumn(['is_edited', 'edit_log', 'edited_at', 'edited_by']);
        });
    }
};
