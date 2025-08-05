<?php

namespace App\Providers;

use App\Http\Routing\ResourceRegistrar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
       if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        
        if (env('DB_LOG') == 'true') {
            DB::listen(function($query) {
                Log::debug($query->sql, [$query->bindings, $query->time]);
            });
        }

        Carbon::macro('parseInUserTimezone', function ($datetime) {
            return Carbon::parse($datetime, user_timezone());
        });

        Carbon::macro('toServerTime', function () {
            /**
             * @var $this Carbon
             */
            return $this->timezone(date_default_timezone_get());
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $resourceRegistrar = new ResourceRegistrar($this->app['router']);

        $this->app->bind(\Illuminate\Routing\ResourceRegistrar::class, function() use ($resourceRegistrar) {
            return $resourceRegistrar;
        });
    }
}
