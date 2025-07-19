<?php

namespace App\Providers;

use App\Components\LinkComponent;
use Illuminate\Support\ServiceProvider;

class LinkComponentServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton(LinkComponent::class);
    }
}
