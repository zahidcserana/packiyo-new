<?php

namespace App\Providers;

use App\Components\PurchaseOrderBillingCacheComponent;
use Illuminate\Support\ServiceProvider;

class PurchaseOrderBillingCacheServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PurchaseOrderBillingCacheComponent::class, function () {
            return new PurchaseOrderBillingCacheComponent();
        });
    }
}
