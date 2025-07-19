<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AddressBook;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressBookPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the addressBook.
     *
     * @param User $user
     * @param  \App\Models\AddressBook  $addressBook
     * @return mixed
     */
    public function view(User $user, AddressBook $addressBook)
    {
        return $user->isAdmin() || $addressBook->customer->hasUser($user->id);
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
     * Determine whether the user can create orders.
     *
     * @param User $user
     * @param $data
     * @return mixed
     */
    public function create(User $user, $data = null)
    {
        return true;
    }

    public function batchStore(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the addressBook.
     *
     * @param User $user
     * @param  $data
     * @return mixed
     */
    public function update(User $user, $data = null)
    {
        return true;
    }

    public function batchUpdate(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the addressBook.
     *
     * @param User $user
     * @param  $data
     * @return mixed
     */
    public function delete(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ?: app('request')->input();

        if (isset($data['id']) && $addressBook = AddressBook::find($data['id'])) {
            return $user->hasCustomer($addressBook->customer_id);
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
