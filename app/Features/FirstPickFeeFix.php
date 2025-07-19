<?php

namespace App\Features;

use App\Models\Customer;
use Carbon\Carbon;

class FirstPickFeeFix
{
    const DATE_OF_RELEASE = '2024-04-15';

    public function resolve(Customer $customer): bool
    {
        if($customer->is3plChild()){
            $customer = $customer->parent;
        }

        return $customer->is3pl() && $customer->created_at->isAfter(new Carbon(self::DATE_OF_RELEASE));
    }
}
