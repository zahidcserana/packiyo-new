<?php

namespace App\Jobs\Billing;

use App\Components\InvoiceComponent;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerationSuccessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;
    public $user;

    public $timeout = 1800;

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
     * @param InvoiceComponent $invoiceComponent
     * @return void
     */
    public function handle(InvoiceComponent $invoiceComponent): void
    {
        $this->invoice->setInvoiceStatus(InvoiceStatus::DONE_STATUS);
        $this->invoice->save();

        $invoiceComponent->sendInvoiceSuccessEmail($this->user, $this->invoice);
    }

}
