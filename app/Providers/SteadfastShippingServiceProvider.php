<?php

namespace App\Providers;

use App\Components\Shipping\Providers\SteadfastShippingProvider;
use Illuminate\Support\ServiceProvider;

class SteadfastShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('steadfastShipping', fn () => new SteadfastShippingProvider());
    }

    public function provides(): array
    {
        return ['steadfastShipping'];
    }
}
