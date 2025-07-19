<?php

namespace App\Policies;

use App\Models\AutomationConditions\OrderLineItemCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderLineItemConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderLineItemCondition $condition)
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
