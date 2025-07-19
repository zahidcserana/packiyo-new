<?php

namespace App\Providers;

use App\Components\Shipping\Providers\TribirdShippingProvider;
use Illuminate\Support\ServiceProvider;

class TribirdShippingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('tribirdShipping', function () {
            return new TribirdShippingProvider();
        });
    }

    public function provides()
    {
        return [
            'tribirdShipping'
        ];
    }
}
