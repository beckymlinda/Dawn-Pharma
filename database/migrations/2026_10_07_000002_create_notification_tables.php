<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Email addresses that receive notifications (e.g. the Z report)
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // History of every notification email sent (or failed)
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('register_id')->nullable()->constrained('registers')->nullOnDelete();
            $table->text('recipients');
            $table->string('subject')->nullable();
            $table->enum('status', ['sent', 'failed']);
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_recipients');
    }
};
