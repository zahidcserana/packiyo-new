<?php

namespace App\Policies;

use App\Models\AutomationActions\AddLineItemAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddLineItemActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, AddLineItemAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
