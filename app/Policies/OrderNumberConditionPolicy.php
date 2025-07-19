<?php

namespace App\Policies;

use App\Models\AutomationConditions\OrderNumberCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderNumberConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderNumberCondition $condition)
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
