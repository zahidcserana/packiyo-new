<?php

namespace App\Policies;

use App\Models\AutomationConditions\ShipToStateCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipToStateConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ShipToStateCondition $condition)
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
