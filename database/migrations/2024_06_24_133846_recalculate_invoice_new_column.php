<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecalculateInvoiceNewColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('recalculated_from_invoice_id')
                ->nullable()
                ->comment('Stores the ID of a recalculated invoice, allowing reference to another invoice record');

            $table->foreign('recalculated_from_invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('set null');
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
            // Drop the column when rolling back the migration
            $table->dropForeign(['recalculated_from_invoice_id']);
            $table->dropColumn('recalculated_from_invoice_id');
        });
    }
}
