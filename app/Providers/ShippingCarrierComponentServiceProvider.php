<?php

namespace App\Providers;

use App\Components\ShippingCarrierComponent;
use Illuminate\Support\ServiceProvider;

class ShippingCarrierComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('shippingCarrier', function () {
            return new ShippingCarrierComponent();
        });
    }

    public function provides()
    {
        return [
            'shippingCarrier'
        ];
    }
}
