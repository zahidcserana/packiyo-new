<?php

use App\Models\{BillingRate,
    CacheDocuments\PurchaseOrderCacheDocument,
    CacheDocuments\PurchaseOrderChargeCacheDocument,
    RateCard};
use Carbon\Carbon;

trait PurchaseRatesSteps
{
    protected BillingRate|null $rateInScope = null;

    /**
     * @Given a billing rate :rateName on rate card :cardName with :fee Fee
     */
    public function aBillingRateOnRateCard(string $rateName, string $cardName, string $fee): void
    {
        $settings = [
            'fee' => $fee,
        ];
        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        $this->rateInScope = BillingRate::factory()->for($rateCard)->create([
            'type' => BillingRate::PURCHASE_ORDER,
            'name' => $rateName,
            'settings' => $settings
        ]);
    }

    /**
     * @Given disable a billing rate :billingRateName
     */
    public function aBillingRateDisable(string $billingRateName): void
    {
        $billingRate = BillingRate::where('name', $billingRateName)->firstOrFail();
        $billingRate->is_enabled = false;
        $billingRate->save();
    }


    /**
     * @Given billing rate :rateName has Fee :fee
     */
    public function BillingRateHasFee(string $rateName, string $fee): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::PURCHASE_ORDER,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }


    /**
     * @When we lost the purchase caches for the order number :orderNumber
     */
    public function weLostThePurchaseCache(string $orderNumber)
    {
        $shipmentDocument = PurchaseOrderCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber])->get();
        $shipmentDocument->each->delete();
    }
    /**
     * @When we lost the purchase charge caches for the order number :orderNumber
     */
    public function weLostThePurchaseChargeCache(string $orderNumber)
    {
        $shipmentDocument = PurchaseOrderChargeCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber])->get();
        $shipmentDocument->each->delete();
    }



    /**
     * @Then a new :count purchase order charged item for :amount has a quantity of :quantity and the description :description was generated
     */
    public function aNewPurchaseOrderChargedItemForHasAQuantityOfAndTheDescriptionWasGenerated(string $count, ?string $amount, string $quantity, string $description): void
    {
        if (empty($amount)) {
            return;
        }

        $purchaseOrderCacheDoc = PurchaseOrderChargeCacheDocument::all();
        $chargeDocument = $purchaseOrderCacheDoc->first();
        $charges = collect([$chargeDocument->getCharges()]);
        $this->checkShippingDocumentForFees($charges, $description, $amount, $quantity, $count);
    }

    /**
     * @When a purchase order rate :rateName was updated at :date
     */
    public function aPurchaseOrderRateWasUpdatedAt($rateName, $date)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::PURCHASE_ORDER,
            'name' => $rateName
        ])->firstOrFail();
        $rate->updated_at = Carbon::parse($date);
        $rate->save();
    }

}
