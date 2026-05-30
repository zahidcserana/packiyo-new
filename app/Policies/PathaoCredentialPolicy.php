<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PathaoCredential;
use Illuminate\Auth\Access\HandlesAuthorization;

class PathaoCredentialPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the pathao credential.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PathaoCredential $pathaoCredential
     * @return mixed
     */
    public function view(User $user, PathaoCredential $pathaoCredential)
    {
        return $user->isAdmin() || $user->canAccessCustomer($pathaoCredential->customer_id);
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
     * Determine whether the user can create pathao credential.
     *
     * @param  \App\Models\User  $user
     * @param  $data
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
     * Determine whether the user can update the pathao credential.
     *
     * @param  \App\Models\User  $user
     * @param  $data
     * @return mixed
     */
    public function update(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ? $data : app('request')->input();

        if (isset($data['id']) && $pathaoCredential = PathaoCredential::find($data['id'])) {
            if ($user->canAccessCustomer($pathaoCredential->customer_id) == false) {
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
     * Determine whether the user can delete the pathao credential.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PathaoCredential $pathaoCredential
     * @return mixed
     */
    public function delete(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ? $data : app('request')->input();

        if (isset($data['id']) && $pathaoCredential = PathaoCredential::find($data['id'])) {
            return $user->canAccessCustomer($pathaoCredential->customer_id);
        }

        return true;
    }

    public function batchDelete(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $key => $data) {
            if ($this->delete($user, $data) == false) {
                return false;
            }
        }

        return true;
    }
}
