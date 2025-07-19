<?php

namespace App\Providers;

use App\Components\ReceivingBillingCalculatorComponent;
use Illuminate\Support\ServiceProvider;

class ReceivingBillingCalculatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ReceivingBillingCalculatorComponent::class, function () {
            return new ReceivingBillingCalculatorComponent();
        });
    }
}
