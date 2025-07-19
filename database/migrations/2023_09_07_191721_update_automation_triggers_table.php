<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutomationTriggersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('automation_triggers', function (Blueprint $table) {
            $table->string('text_pattern')->nullable();
            $table->string('comparison_operator')->enum([
                '==',
                '<',
                '>',
                '<=',
                '>=',
                'some_equals',
                'none_equals',
                'some_starts_with',
                'none_starts_with',
                'some_ends_with',
                'none_ends_with',
                'some_contains',
                'none_contains',
                'matches',
                'not_matches',
                'starts_with_match',
                'not_starts_with_match',
                'ends_with_match',
                'not_ends_with_match',
                'contains_match',
                'not_contains_match'
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('automation_triggers', function (Blueprint $table) {
            $table->string('comparison_operator')->enum([
                '==',
                '<',
                '>',
                '<=',
                '>=',
                'some_equals',
                'none_equals',
                'some_starts_with',
                'none_starts_with',
                'some_ends_with',
                'none_ends_with',
                'some_contains',
                'none_contains'
            ])->nullable()->change();
            $table->dropColumn('text_pattern');
        });
    }
}
