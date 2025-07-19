<?php

namespace App\Policies;

use App\Models\AutomationConditions\TotalOrderAmountCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TotalOrderAmountConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, TotalOrderAmountCondition $condition)
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
