<?php

namespace App\Components\BillingRates\PickingStategies;

use App\Features\Wallet;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class PickingBillingRateStrategyFactory
{
    public static function walletFeatureEnable(Customer $customer): bool
    {
        return $customer->hasFeature(Wallet::class);
    }

    public static function getPickingStrategy(Customer $customer): PickingStrategyInterface
    {
        if (!self::hasMongoConnection()) {
            return new MysqlDataProcessingStrategy();
        }

        if (!self::walletFeatureEnable($customer)) {
            return new MysqlDataProcessingStrategy();
        }

        return new MongoDataProcessingStrategy();
    }

    private static function hasMongoConnection(): bool
    {
        try {
            DB::connection('mongodb')->listCollections();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
