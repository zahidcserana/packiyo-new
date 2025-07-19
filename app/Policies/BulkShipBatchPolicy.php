<?php

namespace App\Policies;

use App\Models\BulkShipBatch;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BulkShipBatchPolicy
{
    use HandlesAuthorization;

    public function before(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function bulkShipBatchShipping(User $user, BulkShipBatch $bulkShipBatch)
    {
        return $this->userCanAlterBatch($user, $bulkShipBatch);
    }

    public function bulkShipBatchShip(User $user, BulkShipBatch $bulkShipBatch)
    {
        return $this->userCanAlterBatch($user, $bulkShipBatch);
    }

    public function show(User $user, BulkShipBatch $bulkShipBatch)
    {
        return $this->userCanAlterBatch($user, $bulkShipBatch);
    }

    public function canUnlockBatch(User $user, BulkShipBatch $bulkShipBatch)
    {
        $taskUserId = Task::find($bulkShipBatch->lock_task_id)?->user_id;

        if ($taskUserId && $taskUserId !== $user->id) {
            return false;
        }

        return true;
    }

    private function userCanAlterBatch(User $user, BulkShipBatch $bulkShipBatch)
    {
        return $user->canAccessCustomer($bulkShipBatch->customer_id)
            && $this->canUnlockBatch($user, $bulkShipBatch);
    }

}
