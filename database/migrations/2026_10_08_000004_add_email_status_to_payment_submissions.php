<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Whether the "new proof of payment" email reached the software owner, so failed ones are retried */
    public function up(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('tenant_payment_id');
            $table->unsignedTinyInteger('notify_attempts')->default(0)->after('notified_at');
            $table->string('notify_error', 500)->nullable()->after('notify_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->dropColumn(['notified_at', 'notify_attempts', 'notify_error']);
        });
    }
};
