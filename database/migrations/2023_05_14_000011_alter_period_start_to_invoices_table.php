<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPeriodStartToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dateTime('period_start')->change();
            $table->dateTime('period_end')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('period_start');
            $table->dropColumn('period_end');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('period_start');
            $table->timestamp('period_end');
        });
    }
}
