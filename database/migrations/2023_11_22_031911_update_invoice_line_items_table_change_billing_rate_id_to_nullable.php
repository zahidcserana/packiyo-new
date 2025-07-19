<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInvoiceLineItemsTableChangeBillingRateIdToNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->unsignedInteger('billing_rate_id')->nullable()->default(null)->change();
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
            $table->dropForeign('invoice_line_items_billing_rate_id_foreign');
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->unsignedInteger('billing_rate_id')->nullable(false)->change();
            $table->foreign('billing_rate_id')
                ->references('id')
                ->on('billing_rates');
        });
    }
}
