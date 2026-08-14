<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('offer_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 140);              // e.g. "Eid Special Collection"
            $table->string('subtitle', 240)->nullable();
            $table->string('badge', 60)->nullable();   // festive ribbon, e.g. "🌙 Eid Mubarak"
            $table->string('discount_label', 60)->nullable(); // e.g. "Up to 20% off"
            $table->string('accent', 20)->nullable();  // optional hex, defaults to brand gold
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('offer_campaign_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_campaign_id')->constrained('offer_campaigns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unique(['offer_campaign_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('offer_campaign_products');
        Schema::dropIfExists('offer_campaigns');
    }
};
