<?php

namespace App\Providers;

use App\Components\ExternalCarrierCredentialComponent;
use Illuminate\Support\ServiceProvider;

class ExternalCarrierCredentialComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('externalCarrierCredential', function (){
            return new ExternalCarrierCredentialComponent();
        });
    }

    public function provides()
    {
        return [
            'externalCarrierCredential'
        ];
    }
}
