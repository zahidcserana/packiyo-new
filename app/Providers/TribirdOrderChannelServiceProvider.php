<?php

namespace App\Providers;

use App\Components\OrderChannel\Providers\TribirdOrderChannelProvider;
use Illuminate\Support\ServiceProvider;

class TribirdOrderChannelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('tribirdOrderChannel', function () {
            return new TribirdOrderChannelProvider();
        });
    }

    public function provides()
    {
        return [
            'tribirdOrderChannel'
        ];
    }
}
