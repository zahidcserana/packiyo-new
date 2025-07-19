<?php

namespace App\Providers;

use App\Components\WholesaleComponent;
use App\Components\WholesaleIntegrationsComponent;
use Illuminate\Support\ServiceProvider;

class WholesaleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WholesaleIntegrationsComponent::class);
        $this->app->singleton(WholesaleComponent::class);
    }
}
