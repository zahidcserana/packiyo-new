<?php

namespace App\Providers;

use App\Components\BulkPrintComponent;
use Illuminate\Support\ServiceProvider;

class BulkPrintComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('bulkPrint', function (){
            return new BulkPrintComponent();
        });
    }

    public function provides(): array
    {
        return [
            'bulkPrint',
        ];
    }
}
