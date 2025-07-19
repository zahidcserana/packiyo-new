<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPeriodEndToInvoiceLineItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
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
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropColumn('period_end');
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->timestamp('period_end');
        });
    }
}
