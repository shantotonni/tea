<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();
            $table->string('image', 255);
            $table->string('badge', 60)->nullable();
            $table->string('eyebrow', 120)->nullable();
            $table->string('title', 160);          // \n allowed for line break
            $table->string('text', 300)->nullable();
            $table->string('target', 160)->nullable();
            $table->string('cta', 80)->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('promo_banners');
    }
};
