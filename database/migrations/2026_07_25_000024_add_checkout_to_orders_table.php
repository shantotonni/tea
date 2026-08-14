<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->after('customer_email');
            $table->string('address', 255)->nullable()->after('phone');
            $table->string('city', 80)->nullable()->after('address');
            $table->string('payment_method', 40)->default('Cash on Delivery')->after('city');
            $table->unsignedInteger('subtotal')->default(0)->after('items_count');
            $table->unsignedInteger('shipping')->default(0)->after('subtotal');
            $table->text('note')->nullable()->after('shipping');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'city', 'payment_method', 'subtotal', 'shipping', 'note']);
        });
    }
};
