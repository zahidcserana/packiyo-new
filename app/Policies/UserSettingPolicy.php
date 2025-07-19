<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tag;
use App\Models\UserSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserSettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the tag.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return true;
    }
    /**
     * Determine whether the user can create tags.
     *
     * @param  \App\Models\User  $user
     * @param  $data
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->isAdmin();
    }

    public function batchStore(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the tag.
     *
     * @param User $user
     * @param UserSetting $userSetting
     * @return mixed
     */
    public function update(User $user, UserSetting $userSetting): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id == $userSetting->user_id;
    }

    public function batchUpdate(User $user)
    {
        return $user->isAdmin();
    }

    public function delete(User $user, UserSetting $userSetting)
    {
        return $user->isAdmin() || $userSetting->user_id == $user->id;
    }

    public function batchDelete(User $user, UserSetting $userSetting)
    {
        return $user->isAdmin() || $userSetting->user_id == $user->id;
    }

}
