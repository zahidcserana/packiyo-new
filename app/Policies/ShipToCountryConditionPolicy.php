<?php

namespace App\Policies;

use App\Models\AutomationConditions\ShipToCountryCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipToCountryConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ShipToCountryCondition $condition)
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
