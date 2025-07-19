<?php

namespace App\Policies;

use App\Models\AutomationConditions\QuantityOrderItemsCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuantityOrderItemsConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, QuantityOrderItemsCondition $condition)
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
