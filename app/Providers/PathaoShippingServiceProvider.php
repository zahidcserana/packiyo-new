<?php

namespace App\Providers;

use App\Components\Shipping\Providers\PathaoShippingProvider;
use Illuminate\Support\ServiceProvider;

class PathaoShippingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('pathaoShipping', function () {
            return new PathaoShippingProvider();
        });
    }

    public function provides()
    {
        return [
            'pathaoShipping'
        ];
    }
}
