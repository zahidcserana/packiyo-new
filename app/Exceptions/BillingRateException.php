<?php

namespace App\Exceptions;

use App\Models\BillingRate;
use Exception;

class BillingRateException extends Exception
{
    public ?BillingRate $rate = null;
    public function __construct(BillingRate $rate, \Throwable $previous = null)
    {
        $this->rate = $rate;
        $message = sprintf("Billing rate error occur: %s , for rate card id: %s billing rate id: %s",
            $previous->getMessage(),
            $rate->rate_card_id,
            $rate->id
        );
        parent::__construct($message, $previous->getCode() ?? 500, $previous);
    }
}
