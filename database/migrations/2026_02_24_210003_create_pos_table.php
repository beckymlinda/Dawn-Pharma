<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('pos', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('return_due', 15, 2)->default(0);
            $table->string('shipping_status')->nullable();
            $table->integer('total_items')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos');
    }
};
