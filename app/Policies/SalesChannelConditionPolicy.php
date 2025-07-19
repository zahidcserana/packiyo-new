<?php

namespace App\Policies;

use App\Models\AutomationConditions\SalesChannelCondition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesChannelConditionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SalesChannelCondition $condition)
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
