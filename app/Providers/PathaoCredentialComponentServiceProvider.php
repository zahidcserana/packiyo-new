<?php

namespace App\Providers;

use App\Components\PathaoCredentialComponent;
use Illuminate\Support\ServiceProvider;

class PathaoCredentialComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('pathaoCredential', function () {
            return new PathaoCredentialComponent();
        });
    }

    public function provides()
    {
        return [
            'pathaoCredential'
        ];
    }
}
