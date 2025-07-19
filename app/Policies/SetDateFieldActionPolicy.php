<?php

namespace App\Policies;

use App\Models\AutomationActions\SetDateFieldAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetDateFieldActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetDateFieldAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
