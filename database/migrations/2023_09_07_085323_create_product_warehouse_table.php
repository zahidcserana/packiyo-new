<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductWarehouseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('product_warehouse', static function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('product_id');
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedInteger('warehouse_id');
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity_on_hand');
            $table->unsignedInteger('quantity_reserved');
            $table->unsignedInteger('quantity_pickable');
            $table->unsignedInteger('quantity_allocated');
            $table->unsignedInteger('quantity_allocated_pickable');
            $table->unsignedInteger('quantity_available');
            $table->unsignedInteger('quantity_to_replenish');
            $table->unsignedInteger('quantity_backordered');
            $table->unsignedInteger('quantity_sell_ahead');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_warehouse');
    }
}
