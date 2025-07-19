<?php

namespace App\Policies;

use App\Models\AutomationActions\SetOperatorHoldAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetOperatorHoldActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetOperatorHoldAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
