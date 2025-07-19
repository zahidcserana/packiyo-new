<?php

namespace App\Providers;

use App\Components\DataWarehouseComponent;
use Illuminate\Support\ServiceProvider;

class DataWarehouseComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('data-warehouse', function () {
            return new DataWarehouseComponent();
        });
    }

    public function provides(): array
    {
        return [
            'data-warehouse'
        ];
    }
}
