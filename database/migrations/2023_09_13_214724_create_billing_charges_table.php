<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_charges', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();

            $table->string('description');
            $table->float('quantity');
            $table->decimal('amount', 12, 2);

            $table->unsignedBigInteger('billing_balance_id');
            $table->foreign('billing_balance_id')
                ->references('id')
                ->on('billing_balances');

            $table->unsignedInteger('billing_rate_id')->nullable();
            $table->foreign('billing_rate_id')
                ->references('id')
                ->on('billing_rates');

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
        Schema::dropIfExists('billing_charges');
    }
}
