<?php

namespace App\Providers;

use App\Components\ShipmentBillingCacheService;
use Illuminate\Support\ServiceProvider;

class ShipmentBillingCacheServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ShipmentBillingCacheService::class, function () {
            return new ShipmentBillingCacheService();
        });
    }
}
