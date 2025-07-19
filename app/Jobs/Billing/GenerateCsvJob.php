<?php

namespace App\Jobs\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;

class GenerateCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoice;
    public $fileInfo;

    public $timeout = 7200;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, array $fileInfo)
    {
        $this->queue = 'billing';

        $this->invoice = $invoice;
        $this->fileInfo = $fileInfo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app('invoiceExport')->exportStreamToCsv($this->invoice, $this->fileInfo);
    }
}
