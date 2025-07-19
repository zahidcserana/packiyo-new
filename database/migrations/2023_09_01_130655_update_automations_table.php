<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutomationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('applies_to')->enum(['owner', 'all', 'some', 'not_some'])->change();

            $table->unsignedBigInteger('original_revision_automation_id')->nullable();
            $table->foreign('original_revision_automation_id')
                ->references('id')
                ->on('automations');

            $table->unsignedBigInteger('previous_revision_automation_id')->nullable();
            $table->foreign('previous_revision_automation_id')
                ->references('id')
                ->on('automations');

            $table->unique(['customer_id', 'position', 'deleted_at'], 'customer_id_position_deleted_at_unique');
            $table->dropUnique('customer_id_position_unique');
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
            $table->unique(['customer_id', 'position'], 'customer_id_position_unique');
            $table->dropUnique('customer_id_position_deleted_at_unique');

            $table->dropForeign('automations_previous_revision_automation_id_foreign');
            $table->dropColumn('previous_revision_automation_id');

            $table->dropForeign('automations_original_revision_automation_id_foreign');
            $table->dropColumn('original_revision_automation_id');

            $table->string('applies_to')->enum(['owner', 'all', 'some'])->change();
            $table->dropColumn('deleted_at');
        });
    }
}
