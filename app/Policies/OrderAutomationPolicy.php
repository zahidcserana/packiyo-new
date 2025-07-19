<?php

namespace App\Policies;

use App\Models\Automations\OrderAutomation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderAutomationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, OrderAutomation $automation)
    {
        return true;
    }

    public function viewCustomer(User $user, OrderAutomation $automation)
    {
        return true;
    }

    public function viewAppliesToCustomers(User $user, OrderAutomation $automation)
    {
        return true;
    }

    public function viewConditions(User $user, OrderAutomation $automation)
    {
        return true;
    }

    public function viewActions(User $user, OrderAutomation $automation)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user)
    {
        return true;
    }

    public function updateAppliesToCustomers(User $user, OrderAutomation $automation)
    {
        return true;
    }

    public function attachAppliesToCustomers(User $user, OrderAutomation $automation)
    {
        return true;
    }

    public function detachAppliesToCustomers(User $user, OrderAutomation $automation)
    {
        return true;
    }
}
