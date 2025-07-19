<?php

namespace App\Policies;

use App\Models\AutomationActions\SetPackingDimensionsAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetPackingDimensionsActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetPackingDimensionsAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
