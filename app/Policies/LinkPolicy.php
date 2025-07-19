<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\User;
use App\Models\Image;
use Illuminate\Auth\Access\HandlesAuthorization;

class LinkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the link.
     *
     * @param User $user
     * @param Link $link
     * @return mixed
     */
    public function view(User $user, Link $link)
    {
        return true;
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
     * Determine whether the user can create links.
     *
     * @param User $user
     * @param null $data
     * @return mixed
     */
    public function create(User $user, $data = null)
    {
        return true;
    }
}
