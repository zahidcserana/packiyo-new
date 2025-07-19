<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerRateCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('customer_rate_card', static function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('rate_card_id');
            $table->unsignedInteger('customer_id');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->foreign('rate_card_id')
                ->references('id')
                ->on('rate_cards')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

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
    public function down(): void
    {
        Schema::dropIfExists('customer_rate_card');
    }
}
