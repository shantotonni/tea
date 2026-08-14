<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->unsignedInteger('max_subtotal')->nullable()->after('min_subtotal');   // cart upper limit
            $table->unsignedInteger('per_customer_limit')->nullable()->after('usage_limit'); // uses per customer
            $table->json('customer_emails')->nullable()->after('per_customer_limit');       // restrict to these buyers
            $table->boolean('new_customers_only')->default(false)->after('customer_emails'); // first-order only
            $table->unsignedInteger('min_customer_spend')->nullable()->after('new_customers_only'); // lifetime spend gate
            $table->json('scope_products')->nullable()->after('min_customer_spend');        // product slugs the code targets
            $table->json('scope_categories')->nullable()->after('scope_products');          // categories the code targets
            $table->boolean('free_shipping')->default(false)->after('scope_categories');    // also waive delivery
        });
    }

    public function down()
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn([
                'max_subtotal', 'per_customer_limit', 'customer_emails', 'new_customers_only',
                'min_customer_spend', 'scope_products', 'scope_categories', 'free_shipping',
            ]);
        });
    }
};
