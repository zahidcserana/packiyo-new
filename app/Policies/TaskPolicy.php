<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskType;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the task.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task  $task
     * @return mixed
     */
    public function view(User $user, Task $task)
    {
        return $user->isAdmin() || $user->canAccessCustomer($task->taskType->customer_id);
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
     * Determine whether the user can create tasks.
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
            if ($user->canAccessCustomer($data['customer_id']) == false ) {
                return false;
            }
        }

        if (isset($data['task_type_id']) && $taskType = TaskType::find($data['task_type_id'])) {
            if ($user->canAccessCustomer($taskType->customer_id) == false) {
                return false;
            }
        }

        if (isset($data['user_id']) && $taskUser = User::find($data['user_id'])) {
            $userIds = app()->user->getAllCustomerUserIds($user);

            return in_array($taskUser->id, $userIds);
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
     * Determine whether the user can update the task.
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

        if (isset($data['id']) && $task = Task::find($data['id'])) {
            if ($user->canAccessCustomer($task->customer_id) == false) {
                return false;
            }
        }

        if (isset($data['customer_id'])) {
            if ($user->canAccessCustomer($data['customer_id']) == false ) {
                return false;
            }
        }

        if (isset($data['task_type_id']) && $taskType = TaskType::find($data['task_type_id'])) {
            if ($user->canAccessCustomer($taskType->customer_id) == false) {
                return false;
            }
        }

        if ($data['user_id'] && $taskUser = User::find($data['user_id'])) {
            $userIds = app()->user->getAllCustomerUserIds($user);

            return in_array($taskUser->id, $userIds);
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

        if (isset($data['id']) && $task = Task::find($data['id'])) {
            return $user->canAccessCustomer($task->customer_id);
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
