<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Image;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImagePolicy
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
     * @param  \App\Models\User  $user
     * @param  $data
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->isAdmin();
    }

    public function batchUpdate(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the tag.
     *
     * @param  \App\Models\User  $user
     * @param  $data
     * @return mixed
     */
    public function delete(User $user, Image $image)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (get_class($image->object) == Customer::class) {
            $customerId = $image->object->id;

            if ($user->canAccessCustomer($customerId)) {
                return true;
            }

            $customer = Customer::find($customerId);

            if ($customer->parent && $customer->parent->users()->where('users.id', $user->id)->exists()) {
                return true;
            }
        }

        return $user->isAdmin();
    }

    public function batchDelete(User $user)
    {
        return $user->isAdmin();
    }

}
