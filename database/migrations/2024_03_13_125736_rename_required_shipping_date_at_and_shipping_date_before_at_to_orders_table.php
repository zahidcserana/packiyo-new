<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameRequiredShippingDateAtAndShippingDateBeforeAtToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('required_shipping_date_at', 'hold_until');
            $table->renameColumn('shipping_date_before_at', 'ship_before');
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
            $table->renameColumn('ship_before', 'shipping_date_before_at');
            $table->renameColumn('hold_until', 'required_shipping_date_at');
        });
    }
}
