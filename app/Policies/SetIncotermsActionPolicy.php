<?php

namespace App\Policies;

use App\Models\AutomationActions\SetIncotermsAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetIncotermsActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetIncotermsAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
