<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShipmentTrackingToReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->unsignedBigInteger('shipment_tracking_id')->nullable();

            $table->foreign('shipment_tracking_id')
                ->references('id')
                ->on('shipment_trackings')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->dropForeign(['shipment_tracking_id']);
            $table->dropColumn('shipment_tracking_id');
        });
    }
}
