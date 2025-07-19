<?php

namespace App\Providers;

use App\Components\BillingRates\Processors\ShippingBillingRateCacheProcessor;
use Illuminate\Support\ServiceProvider;

class ShippingBillingRateCacheProcessorProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton(ShippingBillingRateCacheProcessor::class, function () {
            return new ShippingBillingRateCacheProcessor();
        });
    }
}
