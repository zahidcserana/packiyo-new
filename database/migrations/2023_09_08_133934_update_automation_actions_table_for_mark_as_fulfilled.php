<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutomationActionsTableForMarkAsFulfilled extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('automation_actions', function (Blueprint $table) {
            $table->boolean('ignore_cancelled')->nullable();
            $table->boolean('ignore_fulfilled')->nullable();
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
            $table->dropColumn('ignore_cancelled');
            $table->dropColumn('ignore_fulfilled');
        });
    }
}
