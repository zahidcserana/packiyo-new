<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutomationActionsTableForChargeAdHocRate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('automation_actions', function (Blueprint $table) {
            $table->unsignedFloat('minimum')->nullable();
            $table->unsignedInteger('tolerance')->nullable();
            $table->unsignedFloat('threshold')->nullable();

            $table->unsignedInteger('billing_rate_id')->nullable();
            $table->foreign('billing_rate_id')
                ->references('id')
                ->on('billing_rates');
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
            $table->dropForeign('automation_actions_billing_rate_id_foreign');
            $table->dropColumn('billing_rate_id');

            $table->dropColumn('threshold');
            $table->dropColumn('tolerance');
            $table->dropColumn('minimum');
        });
    }
}
