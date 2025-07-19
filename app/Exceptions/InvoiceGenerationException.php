<?php

namespace App\Exceptions;

use App\Models\Invoice;

class InvoiceGenerationException extends \Exception
{
    public static function createByBillingRateException(BillingRateException $exception, Invoice $invoice): InvoiceGenerationException
    {
        $message = sprintf(
            "Invoice Generation error occur by Billing rate exception: %s, on Invoice number: %s, for client: %s",
            $exception->getMessage(),
            $invoice->invoice_number,
            $invoice->customer->contactInformation->name ?? ''
        );

        return new self(
            $message,
            $exception->getCode(),
            $exception,
        );
    }
}
