<?php

namespace App\Policies;

use App\Models\AutomationConditions\ShippingOptionCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingOptionConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ShippingOptionCondition $condition)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
