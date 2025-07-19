<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BillingRate;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingRatePolicy
{
    use HandlesAuthorization;

    public function create(User $user, $data = null)
    {
        return true;
    }

    public function batchStore(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->create($user, $data)) {
                return false;
            }
        }

        return true;
    }

    public function update(User $user, $data = null)
    {
        return true;
    }

    public function batchUpdate(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->update($user, $data)) {
                return false;
            }
        }

        return true;
    }

    public function delete(User $user, $data = null)
    {
        return true;
    }

    public function batchDelete(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->delete($user, $data)) {
                return false;
            }
        }

        return true;
    }
}
