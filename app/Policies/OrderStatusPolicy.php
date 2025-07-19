<?php

namespace App\Policies;

use App\Models\User;
use App\Models\OrderStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderStatusPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the order status.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\OrderStatus  $orderStatus
     * @return mixed
     */
    public function view(User $user, OrderStatus $orderStatus)
    {
        return $user->isAdmin() || $user->canAccessCustomer($orderStatus->customer_id);
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
     * Determine whether the user can create order statuses.
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
     * Determine whether the user can update the order status.
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

        if (isset($data['id']) && $orderStatus = OrderStatus::find($data['id'])) {
            if ($user->canAccessCustomer($orderStatus->customer_id) == false) {
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

        if (isset($data['id']) && $orderStatus = OrderStatus::find($data['id'])) {
            return $user->canAccessCustomer($orderStatus->customer_id);
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
}
