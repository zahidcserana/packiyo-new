<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalFieldsToPickingBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('picking_batches', function (Blueprint $table) {
            $table->boolean('exclude_single_line_orders');
            $table->integer('tag_id')->nullable();
            $table->string('tag_name')->nullable();
            $table->json('order_ids')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('picking_batches', function (Blueprint $table) {
            $table->dropColumn('exclude_single_line_orders');
            $table->dropColumn('tag_id');
            $table->dropColumn('tag_name');
            $table->dropColumn('order_ids');
        });
    }
}
