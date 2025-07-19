<?php

namespace App\Policies;

use App\Models\Automation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AutomationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Automation $automation)
    {
        return true;
    }

    public function update(User $user, Automation $automation)
    {
        return true;
    }
}
