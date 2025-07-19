<?php

namespace App\Providers;

use App\Components\BillingRates\Processors\PackagingBillingRateCacheProcessor;
use App\Components\BillingRates\Processors\PickingBillingRateCacheProcessor;
use App\Components\BillingRates\Processors\ShippingBillingRateCacheProcessor;
use App\Components\FulfillmentBillingCalculatorService;
use Illuminate\Support\ServiceProvider;

class FulfillmentBillingCalculatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FulfillmentBillingCalculatorService::class, function () {
            return new FulfillmentBillingCalculatorService(
                new PickingBillingRateCacheProcessor(),
                new ShippingBillingRateCacheProcessor(),
                new PackagingBillingRateCacheProcessor()
            ); // is there a way to do this explicitly, so is not need to be pass here ?
        });
    }
}
