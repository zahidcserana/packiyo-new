<?php

namespace App\Policies;

use App\Models\AutomationActions\SetShippingBoxAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetShippingBoxActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetShippingBoxAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
