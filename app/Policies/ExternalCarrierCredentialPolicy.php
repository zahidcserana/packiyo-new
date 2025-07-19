<?php

namespace App\Policies;

use App\Models\ExternalCarrierCredential;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Arr;

class ExternalCarrierCredentialPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the product.
     *
     * @param User $user
     * @param ExternalCarrierCredential $externalCarrierCredential
     * @return mixed
     */
    public function view(User $user, ExternalCarrierCredential $externalCarrierCredential)
    {
        return $user->isAdmin() || $user->canAccessCustomer($externalCarrierCredential->customer_id);
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
     * Determine whether the user can create products.
     *
     * @param User $user
     * @param null $data
     * @return mixed
     */
    public function create(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $customerId = Arr::get($data, 'customer_id');

        if (!$customerId) {
            $customerId = request()->input('customer_id');
        }

        if (!$customerId) {
            $customerId = request()->input('data.relationships.customer.data.id');
        }

        return $user->canAccessCustomer($customerId);
    }

    /**
     * Determine whether the user can update the product.
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

        if (isset($data['customer_id'])) {
            return $user->canAccessCustomer($data['customer_id']);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the task.
     *
     * @param  \App\Models\User  $user
     * @param  $data
     * @return mixed
     */
    public function delete(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ? $data : app('request')->input();

        if (isset($data['id']) && $externalCarrierCredential = ExternalCarrierCredential::find($data['id'])) {
            return $user->canAccessCustomer($externalCarrierCredential->customer_id);
        }

        return true;
    }
}
