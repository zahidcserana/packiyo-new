<?php

namespace App\Policies;

use App\Models\AutomationConditions\OrderTextFieldCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderTextFieldConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderTextFieldCondition $condition)
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
