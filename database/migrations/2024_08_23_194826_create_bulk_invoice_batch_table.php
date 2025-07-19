<?php

use App\Enums\InvoiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBulkInvoiceBatchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bulk_invoice_batches', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('status')->enum([
                InvoiceStatus::CALCULATING_STATUS->value,
                InvoiceStatus::PENDING_STATUS->value,
                InvoiceStatus::FAILED_STATUS->value,
                InvoiceStatus::DONE_STATUS->value,
            ])->nullable(); // Status of the batch (e.g., pending, completed)
            $table->unsignedInteger('recalculated_from_batch_bill_id')
                ->nullable()
                ->comment('Stores the ID of a recalculated invoice batch, allowing reference to another invoice record');
            $table->timestamps();

            // Foreign key to users table (if applicable)
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bulk_invoice_batches');
    }
}
