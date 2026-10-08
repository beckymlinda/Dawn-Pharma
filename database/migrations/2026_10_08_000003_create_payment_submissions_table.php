<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proof of payment uploaded by a shop. The software owner approves it in the
     * admin portal (which records the payment and extends the subscription) or
     * rejects it with a reason the shop can see.
     */
    public function up(): void
    {
        Schema::create('payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('submitted_by')->nullable();     // shop user id
            $table->string('submitted_by_name')->nullable();
            $table->string('submitted_by_email')->nullable();
            $table->unsignedSmallInteger('months');
            $table->decimal('amount', 15, 2);
            $table->string('account', 50);                              // key of tenancy.payment_accounts
            $table->string('reference')->nullable();                    // transaction id
            $table->date('paid_on');
            $table->string('proof_path');                               // private disk
            $table->string('proof_name')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('pending');           // pending | approved | rejected
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->foreignId('tenant_payment_id')->nullable()->constrained('tenant_payments')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_submissions');
    }
};
