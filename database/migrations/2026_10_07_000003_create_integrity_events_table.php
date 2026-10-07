<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only audit trail of actions that can indicate theft or loss.
        // Snapshots are stored in `details`, so the record survives even when
        // the sale / item it refers to is deleted.
        Schema::create('integrity_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('register_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0); // money at risk
            $table->string('description', 500);
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('reviewed')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['severity', 'reviewed']);
        });

        // Cash counted by the cashier at close, so shortages can be detected
        Schema::table('registers', function (Blueprint $table) {
            $table->decimal('expected_cash', 15, 2)->nullable()->after('closing_cash');
            $table->decimal('counted_cash', 15, 2)->nullable()->after('expected_cash');
            $table->decimal('cash_variance', 15, 2)->nullable()->after('counted_cash');
        });
    }

    public function down(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            $table->dropColumn(['expected_cash', 'counted_cash', 'cash_variance']);
        });
        Schema::dropIfExists('integrity_events');
    }
};
