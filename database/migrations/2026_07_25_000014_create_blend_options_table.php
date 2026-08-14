<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('blend_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('blend_questions')->cascadeOnDelete();
            $table->string('opt_id', 40);         // morning | plain | bold ...
            $table->string('title', 80);
            $table->string('hint', 160)->nullable();
            $table->string('icon', 16)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('blend_options');
    }
};
