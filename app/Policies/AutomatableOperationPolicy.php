<?php

namespace App\Policies;

use App\Models\Automations\AutomatableOperation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AutomatableOperationPolicy
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
     * @param  \App\Models\AutomatableOperation  $automationType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AutomatableOperation $automationType)
    {
        return true;
    }

    public function viewSupportedEvents(User $user, AutomatableOperation $automationType)
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
     * @param  \App\Models\AutomatableOperation  $automationType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AutomatableOperation $automationType)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomatableOperation  $automationType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, AutomatableOperation $automationType)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomatableOperation  $automationType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, AutomatableOperation $automationType)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomatableOperation  $automationType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, AutomatableOperation $automationType)
    {
        //
    }
}
