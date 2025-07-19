<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automation_actions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->unsignedInteger('position');
            $table->float('quantity')->nullable(); // Matching the order_items table.
            $table->text('text')->nullable();
            $table->string('insert_method')->enum([
                'replace',
                'prepend',
                'append'
            ])->nullable();
            $table->string('field_name')->enum([
                'operator_hold',
                'payment_hold',
                'fraud_hold',
                'allow_partial'
            ])->nullable();
            $table->boolean('flag_value')->nullable();
            $table->unsignedBigInteger('automation_id');
            $table->foreign('automation_id')
                ->references('id')
                ->on('automations')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->foreign('shipping_method_id')
                ->references('id')
                ->on('shipping_methods');
            $table->timestamps();
            $table->unique(['automation_id', 'position'], 'automation_id_position_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('automation_actions');
    }
}
