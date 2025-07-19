<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShipmentsProductReturnIdToInvoiceLineItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('invoice_line_items', static function (Blueprint $table) {

            $table->unsignedInteger('purchase_order_item_id')->nullable();
            $table->foreign('purchase_order_item_id')
                ->references('id')
                ->on('purchase_order_items');

            $table->unsignedInteger('purchase_order_id')->nullable();
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders');

            $table->unsignedInteger('return_item_id')->nullable();
            $table->foreign('return_item_id')
                ->references('id')
                ->on('return_items');

            $table->unsignedInteger('package_id')->nullable();
            $table->foreign('package_id')
                ->references('id')
                ->on('packages');

            $table->unsignedInteger('package_item_id')->nullable();
            $table->foreign('package_item_id')
                ->references('id')
                ->on('package_order_items');

            $table->unsignedBigInteger('location_type_id')->nullable();
            $table->foreign('location_type_id')
                ->references('id')
                ->on('location_types');
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
            $table->dropForeign('invoice_line_items_purchase_order_item_id_foreign');
            $table->dropColumn('purchase_order_item_id');

            $table->dropForeign('invoice_line_items_purchase_order_id_foreign');
            $table->dropColumn('purchase_order_id');

            $table->dropForeign('invoice_line_items_return_item_id_foreign');
            $table->dropColumn('return_item_id');

            $table->dropForeign('invoice_line_items_package_id_foreign');
            $table->dropColumn('package_id');

            $table->dropForeign('invoice_line_items_package_item_id_foreign');
            $table->dropColumn('package_item_id');

            $table->dropForeign('invoice_line_items_location_type_id_foreign');
            $table->dropColumn('location_type_id');
        });
    }
}
