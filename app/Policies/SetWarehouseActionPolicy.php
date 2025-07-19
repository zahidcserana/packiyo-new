<?php

namespace App\Policies;

use App\Models\AutomationActions\SetWarehouseAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetWarehouseActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetWarehouseAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
