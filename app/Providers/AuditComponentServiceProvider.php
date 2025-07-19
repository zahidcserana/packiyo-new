<?php

namespace App\Providers;

use App\Components\AuditComponent;
use Illuminate\Support\ServiceProvider;

class AuditComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('audit', function () {
            return new AuditComponent();
        });
    }

    public function provides()
    {
        return [
            'audit'
        ];
    }
}
