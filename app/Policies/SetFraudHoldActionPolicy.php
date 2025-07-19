<?php

namespace App\Policies;

use App\Models\AutomationActions\SetFraudHoldAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetFraudHoldActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetFraudHoldAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
