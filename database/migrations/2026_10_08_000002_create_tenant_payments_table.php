<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subscription payments recorded in the admin portal. Shops see them on
     * their Billing page; each payment moves the shop's paid-until date forward.
     */
    public function up(): void
    {
        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('paid_on');
            $table->unsignedSmallInteger('months');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('plan', 30)->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->string('method', 50)->nullable();       // e.g. Airtel Money, bank transfer
            $table->string('reference')->nullable();         // receipt / transaction id
            $table->string('recorded_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
    }
};
