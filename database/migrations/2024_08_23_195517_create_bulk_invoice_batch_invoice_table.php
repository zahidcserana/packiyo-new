<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBulkInvoiceBatchInvoiceTable extends Migration
{
    public function up()
    {
        Schema::create('bulk_invoice_batch_invoice', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('invoice_id')->nullable();
            $table->unsignedInteger('bulk_invoice_batch_id')->nullable();
            $table->timestamps();


            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('bulk_invoice_batch_id')
                ->references('id')
                ->on('bulk_invoice_batches')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bulk_invoice_batch_invoice');
    }
}
