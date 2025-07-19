<?php

namespace App\Jobs\Billing;

use App\Components\Invoice\InvoiceProcessor;
use App\Enums\InvoiceStatus;
use App\Models\BulkInvoiceBatch;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BulkInvoiceBatchSuccessJob
{

    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BulkInvoiceBatch $batch;
    public $user;

    public $timeout = 7200;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BulkInvoiceBatch $batch, ?User $user = null)
    {
        $this->batch = $batch;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            app('invoice')->bulkInvoiceBatchFinishTask($this->batch, $this->user);
        } catch (Throwable $exception) {
            report($exception);
            throw $exception;
        }
    }
}
