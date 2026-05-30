<?php

namespace App\Providers;

use App\Components\SteadfastCredentialComponent;
use Illuminate\Support\ServiceProvider;

class SteadfastCredentialComponentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('steadfastCredential', fn () => new SteadfastCredentialComponent());
    }

    public function provides(): array
    {
        return ['steadfastCredential'];
    }
}
