<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationTriggerMatchesProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automation_trigger_matches_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('automation_trigger_id');
            $table->foreign('automation_trigger_id')
                ->references('id')
                ->on('automation_triggers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });

        Schema::table('automation_triggers', function (Blueprint $table) {
            $table->string('applies_to')->enum(['all', 'some'])->nullable();
            $table->dropForeign('automation_triggers_product_id_foreign');
            $table->dropColumn('product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('automation_triggers', function (Blueprint $table) {
            $table->dropColumn('applies_to');
            $table->unsignedInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });

        Schema::dropIfExists('automation_trigger_matches_product');
    }
}
