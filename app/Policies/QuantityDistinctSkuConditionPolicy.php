<?php

namespace App\Policies;

use App\Models\AutomationConditions\QuantityDistinctSkuCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuantityDistinctSkuConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, QuantityDistinctSkuCondition $condition)
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
