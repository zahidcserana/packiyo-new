<?php

namespace App\Providers;

use App\Components\ZplConverterComponent;
use Illuminate\Support\ServiceProvider;

class ZplConverterComponentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('zplConverter', function (){
            return new ZplConverterComponent();
        });
    }

    public function provides()
    {
        return [
            'zplConverter'
        ];
    }
}
