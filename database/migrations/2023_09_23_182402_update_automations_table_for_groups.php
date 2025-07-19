<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutomationsTableForGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->unsignedBigInteger('group_action_id')->nullable();
            $table->foreign('group_action_id')
                ->references('id')
                ->on('automation_actions');

            $table->unique(
                ['customer_id', 'group_action_id', 'position', 'deleted_at'],
                'customer_id_group_action_id_position_deleted_at_unique'
            );
            $table->dropUnique('customer_id_position_deleted_at_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->unique(['customer_id', 'position', 'deleted_at'], 'customer_id_position_deleted_at_unique');
            $table->dropUnique('customer_id_group_action_id_position_deleted_at_unique');

            $table->dropForeign('automations_group_action_id_foreign');
            $table->dropColumn('group_action_id');
        });
    }
}
