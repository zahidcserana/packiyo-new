<?php

namespace App\Jobs\Billing;

use App\Components\Invoice\InvoiceProcessor;
use App\Enums\InvoiceStatus;
use App\Models\BulkInvoiceBatch;
use App\Models\Invoice;
use App\Models\User;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CalculateBulkInvoiceBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;
    public $user;

    public $timeout = 7200;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, ?User $user = null)
    {
        $this->invoice = $invoice;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Throwable
     */
    public function handle()
    {
        try {
            /** @var BulkInvoiceBatch $batch */
            $batch = $this->invoice->bulkInvoiceBatch()->first();
            if ($batch->getBulkInvoiceBatchStatus() == InvoiceStatus::PENDING_STATUS) {
                $batch->setBulkInvoiceBatchStatus(InvoiceStatus::CALCULATING_STATUS);
            }

            app(InvoiceProcessor::class)->bill($this->invoice);
        }catch (Throwable $exception){
            report($exception);
            throw $exception;
        }
    }
}
