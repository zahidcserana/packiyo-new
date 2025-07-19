<?php

namespace App\Providers;

use App\Components\BillingRates\Processors\PickingBillingRateCacheProcessor;
use Illuminate\Support\ServiceProvider;

class PickingBillingRateCacheProcessorProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PickingBillingRateCacheProcessor::class, function () {
            return new PickingBillingRateCacheProcessor();
        });
    }

}
