<?php

namespace App\Jobs\Billing;

use App\Components\Invoice\InvoiceProcessor;
use App\Components\InvoiceComponent;
use App\Enums\InvoiceStatus;
use App\Features\Wallet;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

class RecalculateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoice;
    public $user;

    public $timeout = 7200;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, ?User $user = null)
    {
        $this->queue = 'billing';

        $this->invoice = $invoice;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @param InvoiceProcessor $processor
     * @param InvoiceComponent $invoiceComponent
     * @return void
     * @throws Throwable
     */
    public function handle(InvoiceProcessor $processor, InvoiceComponent $invoiceComponent): void
    {
        try {
            $processor->bill($this->invoice, $this->user);
            if (!$this->invoice->customer->parent->hasFeature(Wallet::class)) {
                $invoiceComponent->sendInvoiceSuccessEmail($this->user, $this->invoice);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);
            throw $exception;
        }
    }
}
