<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automation_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_revision_automation_id');
            $table->unsignedBigInteger('automation_id');

            $table->foreign('original_revision_automation_id')
                ->references('id')
                ->on('automations')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('automation_id')
                ->references('id')
                ->on('automations')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('automation_revisions');
    }
}
