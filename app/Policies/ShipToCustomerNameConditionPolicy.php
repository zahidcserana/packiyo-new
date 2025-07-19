<?php

namespace App\Policies;

use App\Models\AutomationConditions\ShipToCustomerNameCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipToCustomerNameConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ShipToCustomerNameCondition $condition)
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
