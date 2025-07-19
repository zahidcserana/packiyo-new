<?php

namespace App\Policies;

use App\Models\ContactInformation;
use App\Models\Customer;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactInformationPolicy
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
     * @param ContactInformation $contactInformation
     * @return mixed
     */
    public function update(User $user, ContactInformation $contactInformation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (get_class($contactInformation->object) == Customer::class) {
            $customerId = $contactInformation->object->id;

            if ($user->canAccessCustomer($customerId)) {
                return true;
            }

            $customer = Customer::find($customerId);

            if ($customer->parent && $customer->parent->users()->where('users.id', $user->id)->exists()) {
                return true;
            }
        }

        return false;
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
    public function delete(User $user, Tag $tag)
    {
        return $user->isAdmin() || $tag->customer->hasUser($user->id);
    }

    public function batchDelete(User $user, Tag $tag)
    {
        return $user->isAdmin() || $tag->customer->hasUser($user->id);
    }

}
