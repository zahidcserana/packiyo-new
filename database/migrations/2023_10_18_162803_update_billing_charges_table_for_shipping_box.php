<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBillingChargesTableForShippingBox extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('billing_charges', function (Blueprint $table) {
            $table->decimal('amount', 12, 4)->change();
            $table->unsignedBigInteger('automation_id')->nullable();
            $table->foreign('automation_id')
                ->references('id')
                ->on('automations');
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
        Schema::table('billing_charges', function (Blueprint $table) {
            $table->dropForeign('billing_charges_shipment_id_foreign');
            $table->dropColumn('shipment_id');
            $table->dropForeign('billing_charges_automation_id_foreign');
            $table->dropColumn('automation_id');
            $table->decimal('amount', 12, 2)->change();
        });
    }
}
