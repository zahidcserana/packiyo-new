<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->unsignedInteger('position');
            $table->string('name');
            $table->boolean('is_enabled')->default(0);
            $table->string('applies_to')->enum(['owner', 'all', 'some']);
            $table->json('target_events');
            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers');
            $table->timestamps();
            $table->unique(['customer_id', 'name'], 'customer_id_name_unique');
            $table->unique(['customer_id', 'position'], 'customer_id_position_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('automations');
    }
}
