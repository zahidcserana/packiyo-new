<?php

namespace App\Features;

use App\Models\Customer;
use Carbon\Carbon;

class ThreePLBilling
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(Customer $customer): bool
    {
        // Activate by default for new 3PLs.
        return !empty($customer) && $customer->is3pl() && $customer->created_at->isAfter(new Carbon('2023-08-01'));
    }
}
