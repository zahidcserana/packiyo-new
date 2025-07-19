<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceCalculationDone extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $clientName;
    public $startDate;
    public $endDate;
    public $link;

    public function __construct(
        $clientName,
        $startDate,
        $endDate,
        $link,
    )
    {
        $this->clientName = $clientName;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->link = $link;
    }

    /**
     * @return InvoiceCalculationDone
     */
    public function build(): InvoiceCalculationDone
    {
        return $this->subject('Invoice calculation is done!')
            ->markdown('emails.invoice_calculation_done');
    }
}
