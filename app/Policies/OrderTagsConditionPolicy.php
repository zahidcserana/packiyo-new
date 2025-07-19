<?php

namespace App\Policies;

use App\Models\AutomationConditions\OrderTagsCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderTagsConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, OrderTagsCondition $condition)
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
