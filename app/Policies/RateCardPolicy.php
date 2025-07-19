<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RateCard;
use Illuminate\Auth\Access\HandlesAuthorization;

class RateCardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create RateCards.
     *
     * @param User $user
     * @param RateCard $rateCard
     * @return boolean
     */
    public function view(User $user, RateCard $rateCard)
    {
        return $user->isAdmin();
    }

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

    /**
     * Determine whether the user can update the RateCard.
     *
     * @param User $user
     * @param  $data
     * @return boolean
     */
    public function update(User $user, $data = null)
    {
        $data = $data ?: app('request')->input();

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

    /**
     * Determine whether the user can delete the RateCard.
     *
     * @param User $user
     * @param  $data
     * @return mixed
     */
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
