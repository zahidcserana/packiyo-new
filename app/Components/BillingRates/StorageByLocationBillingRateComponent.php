<?php

namespace App\Components\BillingRates;

use App\Components\BillingRates\StorageByLocationRate\LocationsUsageBillingCalculatorFactory;
use App\Models\BillingRate;
use App\Models\Invoice;
use BadMethodCallException;
use Illuminate\Support\Facades\Log;

class StorageByLocationBillingRateComponent implements BillingRateInterface
{
    const SETTING_NO_LOCATION_TYPE = 'no_location';
    public static string $rateType = 'storage_by_location';

    public function tracksBilledOperations(): bool
    {
        return false;
    }

    public function resetBilledOperations(): void
    {
        throw new BadMethodCallException('This billing rate does not track billed operations.');
    }

    public function calculate(BillingRate $rate, Invoice $invoice): void
    {
        if ($rate->type != $this::$rateType) {
            return;
        }
        Log::channel('billing')->info('[BillingRate] Start ' . $this::$rateType);

        $calculator = LocationsUsageBillingCalculatorFactory::makeCalculator(
            $invoice->customer->parent,
            $invoice,
        );
        $calculator->calculate(
            $rate,
            $invoice
        );

        Log::channel('billing')->info('[BillingRate] End ' . $this::$rateType);
    }
}

