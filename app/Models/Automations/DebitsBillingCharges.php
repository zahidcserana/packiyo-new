<?php

namespace App\Models\Automations;

use App\Models\BillingBalance;
use App\Models\BillingCharge;
use App\Models\Customer;
use App\Models\Warehouse;

trait DebitsBillingCharges
{
    protected function debitCharge(Warehouse $warehouse, Customer $client, BillingCharge $charge)
    {
        $balance = $this->getOrCreateClientBalance($warehouse, $client);
        $charge->billingBalance()->associate($balance);
        $charge->save();
        $balance->debit($charge);
    }

    protected function getOrCreateClientBalance(Warehouse $warehouse, Customer $client): BillingBalance
    {
        return BillingBalance::firstOrCreate([
            'threepl_id' => $client->parent_id,
            'warehouse_id' => $warehouse->id,
            'client_id' => $client->id
        ]);
    }
}
