<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('promo_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('email')->nullable();
            $table->unsignedInteger('discount')->default(0);
            $table->timestamps();

            $table->index(['promo_code_id', 'customer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('promo_redemptions');
    }
};
