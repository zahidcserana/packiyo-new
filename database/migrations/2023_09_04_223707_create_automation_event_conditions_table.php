<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationEventConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automation_event_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->float('number_field_value')->nullable();
            $table->boolean('pending_only')->nullable();
            $table->boolean('ignore_holds')->nullable();
            $table->string('unit_of_measure')->enum([
                'minutes',
                'hours',
                'business_days',
                'days',
                'months',
                'years'
            ])->nullable();
            $table->unsignedBigInteger('automation_id');
            $table->foreign('automation_id')
                ->references('id')
                ->on('automations')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('automation_event_conditions');
    }
}
