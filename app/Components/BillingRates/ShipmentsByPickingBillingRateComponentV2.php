<?php

namespace App\Components\BillingRates;

use App\Components\BillingRates\PickingStategies\PickingBillingRateStrategyFactory;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\BillingRate;
use Illuminate\Support\Facades\Log;

class ShipmentsByPickingBillingRateComponentV2 implements BillingRateInterface
{
    public static $rateType = 'shipments_by_picking_rate_v2';
    public array $billedOrderIds = [];

    /**
     * Not necessarily all units of the SKU in the package will have been billed.
     */
    public array $billedPackageOrderItemIds = [];

    public function tracksBilledOperations(): bool
    {
        return true;
    }

    public function resetBilledOperations(): void
    {
        $this->billedOrderIds = [];
        $this->billedPackageOrderItemIds = [];
    }

    public function calculate(BillingRate $rate, Invoice $invoice): void
    {
        if ($rate->type != $this::$rateType) {
            return;
        }

        Log::channel('billing')->info('[BillingRate] Start ' . $this::$rateType . " for invoice id: " .$invoice->id);
        $customerId = $invoice->customer_id;
        $customer = Customer::find($customerId)->first();

        $pickingStrategy = PickingBillingRateStrategyFactory::getPickingStrategy($customer);
        $pickingStrategy->calculateByRateAndInvoice($rate, $invoice);
        Log::channel('billing')->info('[BillingRate] End ' . $this::$rateType . " for invoice id: " .$invoice->id);
    }
}
