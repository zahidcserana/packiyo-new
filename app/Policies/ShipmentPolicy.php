<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the supplier.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Shipment $shipment
     * @return mixed
     */
    public function view(User $user, Shipment $shipment)
    {
        return $user->isAdmin() || $user->canAccessCustomer($shipment->customer_id);
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
     * Determine whether the user can create shipments.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user, $data = null)
    {
        return false;
    }

    /**
     * Determine whether the user can view the attachments.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Shipment $shipment
     * @return mixed
     */
    public function viewLinks(User $user, Shipment $shipment)
    {
        return true;
    }
}
