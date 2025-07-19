<?php

namespace App\Policies;

use App\Models\AutomationActions\SetAllocationHoldAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetAllocationHoldActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetAllocationHoldAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
