<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('creation_tiles', function (Blueprint $table) {
            $table->id();
            $table->string('image', 255);
            $table->string('label', 120);
            $table->string('meta', 120)->nullable();
            $table->string('target', 160)->nullable(); // product path
            $table->boolean('is_wide')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('creation_tiles');
    }
};
