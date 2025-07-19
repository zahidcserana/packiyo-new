<?php

namespace App\Policies;

use App\Models\AutomationConditions\SubtotalOrderAmountCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubtotalOrderAmountConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SubtotalOrderAmountCondition $condition)
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
