<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetNumericFieldsNotNullableDefaultZeroToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->float('subtotal')->nullable(false)->default(0)->change();
            $table->float('shipping')->nullable(false)->default(0)->change();
            $table->float('tax')->nullable(false)->default(0)->change();
            $table->float('total')->nullable(false)->default(0)->change();
            $table->float('discount')->nullable(false)->default(0)->change();
            $table->float('shipping_discount')->nullable(false)->default(0)->change();
            $table->float('shipping_tax')->nullable(false)->default(0)->change();
            $table->float('packing_length')->nullable(false)->default(0)->change();
            $table->float('packing_width')->nullable(false)->default(0)->change();
            $table->float('packing_height')->nullable(false)->default(0)->change();
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
            $table->float('subtotal')->nullable()->default(null)->change();
            $table->float('shipping')->nullable()->default(null)->change();
            $table->float('tax')->nullable()->default(null)->change();
            $table->float('total')->nullable()->default(null)->change();
            $table->float('discount')->nullable()->default(null)->change();
            $table->float('shipping_discount')->nullable()->default(null)->change();
            $table->float('shipping_tax')->nullable()->default(null)->change();
            $table->float('packing_length')->nullable()->default(null)->change();
            $table->float('packing_width')->nullable()->default(null)->change();
            $table->float('packing_height')->nullable()->default(null)->change();
        });
    }
}
