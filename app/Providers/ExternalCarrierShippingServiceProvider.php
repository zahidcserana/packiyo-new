<?php

namespace App\Providers;

use App\Components\Shipping\Providers\ExternalCarrierShippingProvider;
use Illuminate\Support\ServiceProvider;

class ExternalCarrierShippingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('externalCarrierShipping', function () {
            return new ExternalCarrierShippingProvider();
        });
    }

    public function provides()
    {
        return [
            'externalCarrierShipping'
        ];
    }
}
