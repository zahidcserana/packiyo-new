<?php

namespace App\Policies;

use App\Models\AutomationConditions\OrderWeightCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderWeightConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderWeightCondition $condition)
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
