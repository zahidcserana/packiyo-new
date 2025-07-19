<?php

namespace App\Components\BillingRates\StorageByLocationRate;

use App\Features\Wallet;
use App\Models\CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\App;

class LocationsUsageBillingCalculatorFactory
{
    public static function makeCalculator(Customer $threePl, Invoice $invoice): LocationsUsageBillingCalculator
    {
       if (self::canUseDocDb($threePl, $invoice)) {
            return App::make(DocDbLocationsUsageBillingCalculator::class);
        }

        return App::make(MySqlLocationsUsageBillingCalculator::class);
    }

    private static function canUseDocDb(Customer $threePl, Invoice $invoice): bool
    {
        return self::hasMongoConnection() && $threePl->hasFeature(Wallet::class) && self::hasAllNecessaryDataInDocsDb($invoice);
    }

    private static function hasAllNecessaryDataInDocsDb(Invoice $invoice): bool
    {
        $neededCalculations = $invoice->period_start->diff($invoice->period_end)->days + 1;

        $periodCalculations = WarehouseOccupiedLocationTypesCacheDocument::query()
            ->whereBetween('calendar_date', [$invoice->period_start->toDateString(), $invoice->period_end->toDateString()])
            ->where('customer_id', $invoice->customer_id)
            ->whereIn('warehouse_id', $invoice->customer->parent->warehouses->pluck('id')->toArray())
            ->count();

        return $periodCalculations >= $neededCalculations;
    }

    private static function hasMongoConnection(): bool
    {
        return App::make(MongoDbConnectionTester::class)->testConnection();
    }
}
