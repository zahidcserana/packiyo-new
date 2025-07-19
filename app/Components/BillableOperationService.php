<?php

namespace App\Components;

use App\Components\BillingRates\Charges\StorageByLocation\StorageByLocationChargeComponent;
use App\Exceptions\BillingRateException;
use App\Models\BillingRate;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\RateCard;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BillableOperationService
{
    public function __construct(
        private readonly ShipmentBillingCacheService $shipmentCacheService,
        private readonly FulfillmentBillingCalculatorService $fulfillmentCalculatorService,
        private readonly PurchaseOrderBillingCacheComponent $purchaseOrderBillingCacheService,
        private readonly ReceivingBillingCalculatorComponent $receivingBillingCalculatorService,
        private readonly StorageByLocationChargeComponent $storageByLocationCalculator,
    )
    {

    }

    /**
     * @throws BillingRateException
     */
    public function handleShipmentsOperation(array $shipments, bool $fromListener = false): void
    {
        $shipmentCache = $this->shipmentCacheService->cacheShipments($fromListener, ...$shipments);

        if (!empty($shipmentCache)) {
            if ($fromListener) {
                $this->shipmentCacheCalculation($shipmentCache);
            } else {
                foreach ($shipmentCache as $cache) {
                    $this->shipmentCacheCalculation($cache);
                }
            }
        }
    }

    public function handleReceivingOperation(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrderDoc = $this->purchaseOrderBillingCacheService->cachePurchaseOrder($purchaseOrder);

        if ($purchaseOrderDoc) {
            $customer = Customer::find($purchaseOrderDoc['customer']['id']);
            $rateCards = $this->getRateCardByCustomerId($customer->id);
            foreach ($rateCards as $rateCard) {
                $billingRates = $rateCard->billingRates
                    ->where('type', '=', BillingRate::PURCHASE_ORDER)
                    ->where('is_enabled', '=', true);

                foreach ($billingRates as $billingRate) {
                    $this->receivingBillingCalculatorService->calculate($purchaseOrderDoc, $billingRate);
                }
            }
        }
    }

    public function handleStorageOperation(Customer $client, Warehouse $warehouse, Carbon $calendarDate): void
    {
        $this->storageByLocationCalculator->calculate($client, $warehouse, $calendarDate);
    }

    private function getRateCardByCustomerId($customerId): Collection
    {
        $rateCards = collect();
        $customer = Customer::find($customerId);
        if ($customer) {
            $rateCards = $rateCards->merge($customer->rateCards);
        }
        return $rateCards;
    }

    /**
     * @param ShipmentCacheDocument $shipmentCache
     * @return void
     * @throws BillingRateException
     */
    private function shipmentCacheCalculation(ShipmentCacheDocument $shipmentCache): void
    {
        $rateCards = $this->getRateCardByCustomerId($shipmentCache->getOrderCustomer());
        foreach ($rateCards as $rateCard) {
            $billingRates = $rateCard->billingRates
                ->whereIn('type', BillingRate::SHIPMENT_OPERATION_RATES)
                ->where('is_enabled', '=', true);

            foreach ($billingRates as $billingRate) {
                $this->fulfillmentCalculatorService->calculate($shipmentCache, $billingRate);
            }
        }
    }
}
