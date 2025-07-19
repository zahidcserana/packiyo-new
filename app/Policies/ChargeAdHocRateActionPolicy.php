<?php

namespace App\Policies;

use App\Models\AutomationActions\ChargeAdHocRateAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChargeAdHocRateActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ChargeAdHocRateAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
