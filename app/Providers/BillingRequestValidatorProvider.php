<?php

namespace App\Providers;

use App\Components\BillingRates\RequestValidator\BillingRequestValidator;
use Illuminate\Support\ServiceProvider;

class BillingRequestValidatorProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(BillingRequestValidator::class, function(){
           return new BillingRequestValidator;
        });
    }

}
