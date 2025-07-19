<?php

namespace App\Policies;

use App\Models\AutomationActions\AddGiftNoteAction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddGiftNoteActionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, AddGiftNoteAction $action)
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
