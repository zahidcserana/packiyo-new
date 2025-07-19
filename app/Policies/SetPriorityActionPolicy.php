<?php

namespace App\Policies;

use App\Models\AutomationActions\SetPriorityAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetPriorityActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetPriorityAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
