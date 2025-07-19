<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrdersTableForWholesale extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('is_wholesale')->boolean()->nullable();
            $table->string('external_id', 255)->change();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('is_freight')->boolean()->nullable();
        });

        Schema::table('shipment_labels', function (Blueprint $table) {
            $table->string('scac')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipment_labels', function (Blueprint $table) {
            $table->dropColumn('scac');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('is_freight');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('external_id')->change();
            $table->dropColumn('is_wholesale');
        });
    }
}
