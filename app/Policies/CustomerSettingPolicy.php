<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\CustomerSetting;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerSettingPolicy
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
     * @param CustomerSetting $customerSetting
     * @return mixed
     */
    public function update(User $user, CustomerSetting $customerSetting): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->canAccessCustomer($customerSetting->customer_id)) {
            return true;
        }

        $customer = Customer::find($customerSetting->customer_id);

        if ($customer->parent && $customer->parent->users()->where('users.id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    public function batchUpdate(User $user)
    {
        return $user->isAdmin();
    }

    public function delete(User $user, CustomerSetting $customerSetting)
    {
        return $user->isAdmin() || $user->canAccessCustomer($customerSetting->customer_id);
    }

    public function batchDelete(User $user, CustomerSetting $customerSetting)
    {
        return $user->isAdmin() || $user->canAccessCustomer($customerSetting->customer_id);
    }

}
