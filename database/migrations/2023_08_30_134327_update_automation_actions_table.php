<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutomationActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('automation_actions', function (Blueprint $table) {
            $table->unsignedInteger('shipping_box_id')->nullable();
            $table->foreign('shipping_box_id')
                ->references('id')
                ->on('shipping_boxes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('automation_actions', function (Blueprint $table) {
            $table->dropForeign('automation_actions_shipping_box_id_foreign');
            $table->dropColumn('shipping_box_id');
        });
    }
}
