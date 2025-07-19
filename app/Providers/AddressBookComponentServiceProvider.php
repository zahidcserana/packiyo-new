<?php

namespace App\Providers;

use App\Components\AddressBookComponent;
use Illuminate\Support\ServiceProvider;

class AddressBookComponentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('addressBook', function () {
            return new AddressBookComponent();
        });
    }

    public function provides()
    {
        return [
            'addressBook'
        ];
    }
}
