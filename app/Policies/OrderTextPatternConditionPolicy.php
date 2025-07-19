<?php

namespace App\Policies;

use App\Models\AutomationConditions\OrderTextPatternCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderTextPatternConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderTextPatternCondition $condition)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
