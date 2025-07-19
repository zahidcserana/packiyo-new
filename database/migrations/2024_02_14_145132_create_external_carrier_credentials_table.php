<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalCarrierCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_carrier_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->string('reference')->nullable();
            $table->string('get_carriers_url')->nullable();
            $table->string('create_shipment_label_url')->nullable();
            $table->string('create_return_label_url')->nullable();
            $table->string('void_label_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('external_carrier_credentials');
    }
}
