<?php

namespace App\Providers;

use App\Components\Allocation\{AllocationComponent, MultiWarehouseComponent};
use App\Features\MultiWarehouse;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class AllocationComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('allocation', function () {
            if (Feature::for('instance')->active(MultiWarehouse::class)) {
                return new MultiWarehouseComponent();
            }

            return new AllocationComponent();
        });
    }

    public function provides(): array
    {
        return [
            'allocation'
        ];
    }
}
