<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('offer_campaigns', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('accent')
                ->constrained('promo_codes')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('offer_campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
        });
    }
};
