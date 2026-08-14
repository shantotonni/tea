<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();   // store / notifications / shipping
            $table->string('key')->unique();     // e.g. store.name
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string|bool|int — how to cast on read
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
