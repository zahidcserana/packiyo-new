<?php

namespace App\Policies;

use App\Models\Automations\AutomationActionType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AutomationActionTypePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomationActionType  $automationActionType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AutomationActionType $automationActionType)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomationActionType  $automationActionType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AutomationActionType $automationActionType)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomationActionType  $automationActionType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, AutomationActionType $automationActionType)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomationActionType  $automationActionType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, AutomationActionType $automationActionType)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomationActionType  $automationActionType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, AutomationActionType $automationActionType)
    {
        //
    }
}
