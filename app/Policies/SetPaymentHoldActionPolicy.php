<?php

namespace App\Policies;

use App\Models\AutomationActions\SetPaymentHoldAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetPaymentHoldActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetPaymentHoldAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
