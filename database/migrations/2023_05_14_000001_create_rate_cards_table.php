<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->decimal('monthly_cost', 12)->nullable();
            $table->decimal('per_user_cost', 12)->nullable();
            $table->decimal('per_purchase_order_received_cost', 12)->nullable();
            $table->decimal('per_product_cost', 12)->nullable();
            $table->decimal('per_shipment_cost', 12)->nullable();
            $table->decimal('per_return_cost', 12)->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rate_cards');
    }
}
