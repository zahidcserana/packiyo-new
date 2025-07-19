<?php

namespace App\Policies;

use App\Models\AutomationActions\SetTextFieldAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SetTextFieldActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SetTextFieldAction $action)
    {
        return true;
    }

    public function viewAutomation(User $user, SetTextFieldAction $action)
    {
        return true;
    }
}
