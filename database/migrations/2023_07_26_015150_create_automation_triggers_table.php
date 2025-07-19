<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationTriggersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automation_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->unsignedInteger('position');
            $table->boolean('is_alternative')->default(false);
            $table->string('field_name')->enum([
                'operator_hold',
                'payment_hold',
                'fraud_hold',
                'allow_partial',
                'shipping_method_name',
                'number',
                'shipping_method_name',
                'shippingContactInformation.country.name',
                'shippingContactInformation.country.iso_3166_2',
                'shippingContactInformation.state',
                'shippingContactInformation.city',
                'billingContactInformation.country.name',
                'billingContactInformation.country.iso_3166_2',
                'billingContactInformation.state',
                'billingContactInformation.city'
            ])->nullable();
            $table->string('comparison_operator')->enum([
                '==',
                '<',
                '>',
                '<=',
                '>=',
                'some_equals',
                'none_equals',
                'some_starts_with',
                'none_starts_with',
                'some_ends_with',
                'none_ends_with',
                'some_contains',
                'none_contains'
            ])->nullable();
            $table->string('unit_of_measure')->enum(['lb', 'oz', 'kg', 'g', 'l'])->nullable();
            $table->boolean('flag_value')->nullable();
            $table->float('number_field_value')->nullable();
            $table->json('text_field_values')->nullable();
            $table->unsignedBigInteger('automation_id');
            $table->foreign('automation_id')
                ->references('id')
                ->on('automations')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('order_channel_id')->nullable();
            $table->foreign('order_channel_id')
                ->references('id')
                ->on('order_channels');
            $table->unsignedInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
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
        Schema::dropIfExists('automation_triggers');
    }
}
