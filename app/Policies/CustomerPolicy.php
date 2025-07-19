<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Image;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\CustomerUserRole;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the customer.
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function view(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->canAccessCustomer($customer->id)) {
            return true;
        }

        if ($customer->parent) {
            return $customer->parent->users()->where('users.id', $user->id)->exists();
        }

        return false;
    }
    /**
     * Determine whether the user can view.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create customers.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $customers = app()->user->getSelectedCustomers();

        if ($customers->count() > 1) {
            return true;
        }

        if ($customers->first()->isNotChild()) {
            return true;
        }

        return false;
    }

    public function batchStore(User $user): bool
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->create($user, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can update the customer.
     *
     * @param User $user
     * @param  $data
     * @return mixed
     */
    public function update(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ?: app('request')->input();

        if (isset($data['id'])) {
            return $user->canAccessCustomer($data['id']);
        }

        return true;
    }

    public function batchUpdate(User $user): bool
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->update($user, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can delete the customer.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    public function batchDelete(User $user): bool
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->delete($user, $data)) {
                return false;
            }
        }

        return true;
    }

    public function warehouses(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->canAccessCustomer($customer->id)) {
            return false;
        }

        return true;
    }

    public function users(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->canAccessCustomer($customer->id)) {
            return false;
        }

        return true;
    }

    public function tasks(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->canAccessCustomer($customer->id)) {
            return false;
        }

        return true;
    }

    public function products(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->canAccessCustomer($customer->id)) {
            return false;
        }

        return true;
    }

    public function updateUsers(User $user, Customer $customer)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->canAccessCustomer($customer->id)) {
            return false;
        }

        return CustomerUser::where('user_id', $user->id)->where('customer_id', $customer->id)->where('role_id', CustomerUserRole::ROLE_CUSTOMER_ADMINISTRATOR)->first();
    }

    public function webshipperShippingRates(User $user, Customer $customer)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->canAccessCustomer($customer->id)) {
            return false;
        }

        return true;
    }

    public function billing(User $user, Customer $customer)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($customer->is3pl() && $user->canAccessCustomer($customer->id)) {
            return true;
        }

        return false;
    }

    public function deleteImage(User $user, Image $image)
    {
        return $user->isAdmin() || $user->canAccessCustomer($image->object_id);
    }

    public function easypostCredentials(Customer $customer)
    {
        return true;
    }
}
