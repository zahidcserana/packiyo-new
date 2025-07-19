<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ShippingCarrier;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShippingCarrierPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the shipping carrier.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ShippingCarrier  $shippingCarrier
     * @return mixed
     */
    public function view(User $user, ShippingCarrier $shippingCarrier)
    {
        return $user->isAdmin() || $user->canAccessCustomer($shippingCarrier->customer_id);
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
     * Determine whether the user can create shipping carrier.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ? $data : app('request')->input();

        if (isset($data['customer_id'])) {
            return $user->canAccessCustomer($data['customer_id']);
        }

        return true;
    }

    public function batchStore(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $key => $data) {
            if ($this->create($user, $data) == false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can update the shipping carrier.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ? $data : app('request')->input();

        if (isset($data['id']) && $shippingCarrier = ShippingCarrier::find($data['id'])) {
            if ($user->canAccessCustomer($shippingCarrier->customer_id) == false) {
                return false;
            }
        }

        if (isset($data['customer_id'])) {
            return $user->canAccessCustomer($data['customer_id']);
        }

        return true;
    }

    public function batchUpdate(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $key => $data) {
            if ($this->update($user, $data) == false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can delete the order status.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ? $data : app('request')->input();

        if (isset($data['id']) && $shippingCarrier = ShippingCarrier::find($data['id'])) {
            return $user->canAccessCustomer($shippingCarrier->customer_id);
        }

        return true;
    }

    public function batchDelete(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $key => $data) {
            if ($this->delete( $user, $data) == false) {
                return false;
            }
        }

        return true;
    }

    public function easypost(User $user, ShippingCarrier $carrier): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $carrierAccount = app('easypostShipping')->getCarrierAccount($carrier->credential, $carrier->settings['external_carrier_id']);

        return app('easypostShipping')->matchesEasypostReference($carrier->credential, $carrierAccount);
    }
}
