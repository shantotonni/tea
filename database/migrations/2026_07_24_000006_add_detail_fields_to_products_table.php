<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailFieldsToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating', 2, 1)->default(5.0)->after('reviews');
            $table->boolean('is_featured')->default(false)->after('rating'); // BestSeller pick
            $table->boolean('in_gift_box')->default(false)->after('is_featured'); // Discovery box
            // flexible per-product content shown on the detail page
            // { gallery:[], tasting:{strength,aroma,sweetness,astringency}, facts:{leaf_grade,elevation,harvest,origin} }
            $table->json('details')->nullable()->after('in_gift_box');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rating', 'is_featured', 'in_gift_box', 'details']);
        });
    }
}
