<?php

namespace App\Policies;

use App\Models\AutomationActions\SetShippingMethodAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetShippingMethodActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetShippingMethodAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
