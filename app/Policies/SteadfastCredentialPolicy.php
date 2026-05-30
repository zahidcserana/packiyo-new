<?php

namespace App\Policies;

use App\Models\SteadfastCredential;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SteadfastCredentialPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SteadfastCredential $steadfastCredential): mixed
    {
        return $user->isAdmin() || $user->canAccessCustomer($steadfastCredential->customer_id);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user, mixed $data = null): mixed
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

    public function batchStore(User $user): bool
    {
        foreach (app('request')->input() as $data) {
            if ($this->create($user, $data) == false) {
                return false;
            }
        }

        return true;
    }

    public function update(User $user, mixed $data = null): mixed
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ?: app('request')->input();

        if (isset($data['id']) && $credential = SteadfastCredential::find($data['id'])) {
            if ($user->canAccessCustomer($credential->customer_id) == false) {
                return false;
            }
        }

        if (isset($data['customer_id'])) {
            return $user->canAccessCustomer($data['customer_id']);
        }

        return true;
    }

    public function batchUpdate(User $user): bool
    {
        foreach (app('request')->input() as $data) {
            if ($this->update($user, $data) == false) {
                return false;
            }
        }

        return true;
    }

    public function delete(User $user, mixed $data = null): mixed
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ?: app('request')->input();

        if (isset($data['id']) && $credential = SteadfastCredential::find($data['id'])) {
            return $user->canAccessCustomer($credential->customer_id);
        }

        return true;
    }

    public function batchDelete(User $user): bool
    {
        foreach (app('request')->input() as $data) {
            if ($this->delete($user, $data) == false) {
                return false;
            }
        }

        return true;
    }
}
