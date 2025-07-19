<?php

namespace App\Policies;

use App\Models\Automations\PurchaseOrderAutomation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderAutomationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, PurchaseOrderAutomation $automation)
    {
        return true;
    }
}
