<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShipmentIdToInvoiceLineItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->unsignedInteger('shipment_id')->nullable();
            $table->foreign('shipment_id')
                ->references('id')
                ->on('shipments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropForeign('invoice_line_items_shipment_id_foreign');
            $table->dropColumn('shipment_id');
        });
    }
}
