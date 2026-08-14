<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // stored UPPERCASE
            $table->string('description', 160)->nullable();
            $table->enum('type', ['percent', 'fixed'])->default('percent');
            $table->unsignedInteger('value')->default(0);   // % (0-100) or flat ৳
            $table->unsignedInteger('min_subtotal')->default(0);
            $table->unsignedInteger('max_discount')->nullable(); // cap for percent type
            $table->unsignedInteger('usage_limit')->nullable();  // total redemptions allowed
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('promo_codes');
    }
};
