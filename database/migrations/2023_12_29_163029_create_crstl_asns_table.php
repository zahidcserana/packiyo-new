<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrstlAsnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crstl_asns', function (Blueprint $table) {
            $table->id();

            $table->string('external_shipment_id');
            $table->integer('request_labels_after_ms');
            $table->string('shipping_labels_status');
            $table->string('asn_status');

            $table->unsignedInteger('order_id');
            $table->foreign('order_id')
                ->references('id')
                ->on('orders');

            $table->unsignedInteger('shipment_id');
            $table->foreign('shipment_id')
                ->references('id')
                ->on('shipments');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crstl_asns');
    }
}
