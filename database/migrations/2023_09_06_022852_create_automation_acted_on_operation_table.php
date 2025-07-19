<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationActedOnOperationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('automation_acted_on_order', 'automation_acted_on_operation');
        Schema::table('automation_acted_on_operation', function (Blueprint $table) {
            $table->unsignedBigInteger('original_revision_automation_id')->nullable();
            $table->foreign('original_revision_automation_id', name: 'automation_acted_on_op_original_rev_automation_id_foreign')
                ->references('id')
                ->on('automations')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->dropForeign('automation_acted_on_order_order_id_foreign');
            $table->dropColumn('order_id');

            $table->string('operation_type');
            $table->unsignedBigInteger('operation_id');
            $table->index(['operation_type', 'operation_id'], name: 'automation_acted_on_operation_operation_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('automation_acted_on_operation', 'automation_acted_on_order');
        Schema::table('automation_acted_on_order', function (Blueprint $table) {
            $table->dropIndex('automation_acted_on_operation_operation_index');
            $table->dropColumn('operation_type');
            $table->dropColumn('operation_id');

            $table->unsignedInteger('order_id');
            $table->foreign('order_id')
                ->references('id')
                ->on('orders');

            $table->dropForeign('automation_acted_on_op_original_rev_automation_id_foreign');
            $table->dropColumn('original_revision_automation_id');
        });
    }
}
