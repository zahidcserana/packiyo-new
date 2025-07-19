<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutomationActionsTableToAcceptDate extends Migration
{
    public function up(): void
    {
        Schema::table('automation_actions', function (Blueprint $table) {
            $table->string('unit_of_measure')->nullable();
            $table->float('number_field_value')->nullable();
            $table->json('text_field_values')->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('automation_actions', function (Blueprint $table) {
            $table->dropColumn('unit_of_measure');
            $table->dropColumn('number_field_value');
            $table->dropColumn('text_field_values');
        });
    }
}
