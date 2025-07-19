<?php

namespace App\Policies;

use App\Models\AutomationConditions\OrderIsManualCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderIsManualConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderIsManualCondition $condition)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
