<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIncotermColumnToOrdersAndShippingMethodsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('incoterms')->nullable()->after('shipping_method_id');
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->string('incoterms')->nullable()->after('settings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('incoterms');
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn('incoterms');
        });
    }
}
