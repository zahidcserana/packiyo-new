<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Printer;
use App\Models\PrintJob;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrinterPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Printer $printer) {
        return $user->isAdmin() || $user->canAccessCustomer($printer->customer_id);
    }

    public function viewAny(User $user) {
        return true;
    }

    public function jobs(User $user, Printer $printer) {
        return $user->isAdmin() || $user->canAccessCustomer($printer->customer_id);
    }

    public function disable(User $user, Printer $printer) {
        return $user->isAdmin() || $user->canAccessCustomer($printer->customer_id);
    }

    public function enable(User $user, Printer $printer) {
        return $user->isAdmin() || $user->canAccessCustomer($printer->customer_id);
    }

    public function jobRepeat(User $user, PrintJob $printJob) {
        return $user->isAdmin() || $user->canAccessCustomer($printJob->printer->customer_id);
    }
}
