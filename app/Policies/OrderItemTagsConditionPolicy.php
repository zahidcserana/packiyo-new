<?php

namespace App\Policies;

use App\Models\AutomationConditions\OrderItemTagsCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderItemTagsConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderItemTagsCondition $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
