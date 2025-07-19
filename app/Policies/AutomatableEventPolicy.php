<?php

namespace App\Policies;

use App\Models\Automations\AutomatableEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AutomatableEventPolicy
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
     * @param  \App\Models\AutomatableEvent  $automatableEvent
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AutomatableEvent $automatableEvent)
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
     * @param  \App\Models\AutomatableEvent  $automatableEvent
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AutomatableEvent $automatableEvent)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomatableEvent  $automatableEvent
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, AutomatableEvent $automatableEvent)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomatableEvent  $automatableEvent
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, AutomatableEvent $automatableEvent)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AutomatableEvent  $automatableEvent
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, AutomatableEvent $automatableEvent)
    {
        //
    }
}
