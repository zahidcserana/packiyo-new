<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommercialInvoiceSignatureAndCommercialInvoiceSignatureToEasypostCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('easypost_credentials', function (Blueprint $table) {
            $table->string('commercial_invoice_signature')->nullable();
            $table->string('commercial_invoice_letterhead')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('easypost_credentials', function (Blueprint $table) {
            $table->dropColumn('commercial_invoice_signature');
            $table->dropColumn('commercial_invoice_letterhead');
        });
    }
}
