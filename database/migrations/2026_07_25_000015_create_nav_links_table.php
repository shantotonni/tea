<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('nav_links', function (Blueprint $table) {
            $table->id();
            $table->string('label', 60);
            $table->string('target', 60);       // section id (scroll) or path
            $table->boolean('is_cta')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nav_links');
    }
};
