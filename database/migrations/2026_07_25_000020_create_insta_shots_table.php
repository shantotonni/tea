<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('insta_shots', function (Blueprint $table) {
            $table->id();
            $table->string('image', 255);
            $table->string('caption', 160)->nullable();
            $table->unsignedInteger('likes')->default(0);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('insta_shots');
    }
};
