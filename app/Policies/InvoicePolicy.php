<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the invoice.
     *
     * @param User $user
     * @param Invoice $invoice
     * @return bool
     */
    public function view(User $user, Invoice $invoice)
    {
        return $user->isAdmin() || $user->canAccessCustomer($invoice->customer_id);
    }

    /**
     * Determine whether the user can create invoices.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the invoice.
     *
     * @param User $user
     * @param Invoice $invoice
     * @return mixed
     */
    public function delete(User $user, Invoice $invoice)
    {
        return $user->isAdmin() || $user->canAccessCustomer($invoice->customer_id);
    }
}
