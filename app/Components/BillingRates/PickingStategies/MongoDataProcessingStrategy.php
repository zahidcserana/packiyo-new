<?php

namespace App\Components\BillingRates\PickingStategies;

use App\Features\AllowOverlappingRates;
use App\Models\BillingRate;
use App\Models\CacheDocuments\DataTransferObject\BillingChargeItemDto;
use App\Models\CacheDocuments\PickingBillingRateShipmentCacheDocument;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\Invoice;
use App\Traits\BillingCalculatorTrait;
use App\Traits\MongoBillingCalculatorTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;
use stdClass;

class MongoDataProcessingStrategy implements PickingStrategyInterface
{
    use BillingCalculatorTrait, MongoBillingCalculatorTrait;
    public array $billedOrderIds = [];

    public array $billedPackageOrderItemIds = [];

    public array $originalShippedOrderItems = [];
    public array $chargedDtos = [];
    public string $periodEnd;

    //@todo stop work until further notice
    public function calculateByRateAndInvoice(BillingRate $rate, Invoice $invoice): void
    {
        Log::channel('billing')->info('[BillingRate] Start using MONGO strategy');
        $customerId = $invoice->customer_id;
        $from = Carbon::parse($invoice->period_start);
        $to = Carbon::parse($invoice->period_end);
        $settings = $this->getSettingsFromBillingRate($rate);

        $documents = ShipmentCacheDocument::where('customer_id', $customerId)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        foreach ($documents as $document) {
            foreach ($document->getShipments() as $shipment) {
                //$this->createPickingBillingRateDocuments($rate, $settings, $shipment, $customerId, $to->toDateTimeString());
            }
        }

        Log::channel('billing')->info('[BillingRate] End using MONGO strategy');
    }

    private function createPickingBillingRateDocuments(BillingRate $rate, array $settings, array $shipment, int $customerId, string $periodEnd): void
    {
        $rateShouldApply = $this->rateApplies($settings, $shipment);

        if (!$rateShouldApply) {
            return;
        }

        $this->periodEnd = $periodEnd;
        $shippedOrderItems = $this->getShippedOrderItems($settings, $shipment);
        $this->originalShippedOrderItems = $shippedOrderItems;

        $this->chargeFlatFee($rate, $settings, $shipment, $shippedOrderItems);
        $this->chargeFirstPickFee($rate, $settings, $shipment, $shippedOrderItems);
        //@TODOS
        $this->chargeFirstPickOfAdditionalSku($rate, $settings, $shipment, $shippedOrderItems);

        if (array_key_exists('pick_range_fees', $settings) && is_array($settings['pick_range_fees'])) {
            $this->chargeRangePickFees($rate, $settings['pick_range_fees'], $shipment, $shippedOrderItems);
        }

        $this->chargeRemainingPicks($rate, $settings, $shipment, $shippedOrderItems);
        //END @TODOS
        $shippedOrderItems = [];
        $documentData = PickingBillingRateShipmentCacheDocument::make(
            $this->originalShippedOrderItems,
            $this->chargedDtos,
            $rate,
            $customerId,
            $shipment['order_id']
        );

        $documentData->save();
    }

    /**
     * @param array $settings
     * @param array $shipment
     * @return bool
     */
    private function rateApplies(array $settings, array $shipment): bool
    {
        $rateShouldApply = true;

        if (
            !$settings['if_no_other_rate_applies']
            && (!empty($settings['match_has_order_tag']) || !empty($settings['match_has_not_order_tag']))
        ) {
            $orderTags = $shipment->order->tags->map(static function ($tag) {
                return $tag['name'];
            })->toArray();

            $rateShouldApply = empty(array_diff($settings['match_has_order_tag'], $orderTags))
                && empty(array_intersect($orderTags, $settings['match_has_not_order_tag']));
        }
        return $rateShouldApply;
    }

    private function chargeFlatFee(BillingRate $rate, array $settings, array $shipment, array $shippedOrderItems): void
    {
        if (Feature::for('instance')->active(AllowOverlappingRates::class)) {
            $shouldChargeFlatFee = true;
        } else {
            $shouldChargeFlatFee = !in_array($shipment['order_id'], $this->billedOrderIds);
        }

        if (
            isset($settings['charge_flat_fee'])
            && $settings['charge_flat_fee']
            && $settings['flat_fee'] > 0
            && !empty($shippedOrderItems)
            && $shouldChargeFlatFee
        ) {
            $description = 'Flat fee for order number ' . $shipment['order_number'];
            $quantity = 1;

            $settings = ['fee' => $settings['flat_fee'], 'shipment_id' => $shipment['id']];
            $this->addChargeCacheDocumentItem(
                $description,
                $rate,
                $settings,
                $quantity
            );

            $this->markOrderBilled($shipment);
        }
    }

    private function chargeFirstPickFee(BillingRate $rate, array $settings, array $shipment, array $shippedOrderItems): void
    {
        $firstPickFee = $settings['first_pick_fee'];
        $alreadyApply = false;

        foreach ($shippedOrderItems as $sku => $packageOrderItems) {
            if ($alreadyApply) {
                break;
            }

            $description = $this->composeDescription($shipment, 'SKU: ' . $sku . ' first pick fee');
            Log::channel('billing')->info('[BillingRate] first pick fee' . $description);
            $index = 0;
            $quantity = 1;

            $settingValues = [
                'fee' => $firstPickFee,
                'package_item_id' => $packageOrderItems[$index]->packageOrderItemId,
                'shipment_id' => $shipment['id']
            ];

            $this->addChargeCacheDocumentItem(
                $description,
                $rate,
                $settingValues,
                $quantity
            );

            $this->markPackageOrderItemBilled($packageOrderItems[$index]);
            $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
            $alreadyApply = true;
        }
    }
    private function chargeFirstPickOfAdditionalSku(BillingRate $rate, array $settings, array $shipment, array $shippedOrderItems): void
    {
        if (!$this->moreThanOneSKUs() || empty($shippedOrderItems)) {
            return;
        }

        if ($settings['charge_additional_sku_picks']) {

            $additionalSkus = array_slice(array_keys($shippedOrderItems), $this->sameAmountOfSKUs($shippedOrderItems) ? 1 : 0);

            foreach ($additionalSkus as $sku) {
                $description = $this->composeDescription(
                    $shipment,
                    'SKU: ' . $sku . ' first pick of additional SKU'
                );

                Log::channel('billing')->info('[BillingRate] Additional SKU fee' . $description);
                $index = 0;
                $quantity = 1;
                $settingValues = [
                    'fee' => $settings['additional_sku_pick_fee'],
                    'package_item_id' => $shippedOrderItems[$sku][$index]->packageOrderItemId,
                    'shipment_id' => $shipment['id']
                ];
                $this->addChargeCacheDocumentItem(
                    $description,
                    $rate,
                    $settingValues,
                    $quantity
                );

                $this->markPackageOrderItemBilled($shippedOrderItems[$sku][$index]);
                $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
            }
        }
    }

    private function addChargeCacheDocumentItem(
        $description,
        $rate,
        $settings,
        $quantity
    ): void
    {
        $chargedDto = new BillingChargeItemDto(
            $description,
            $rate,
            $settings,
            $quantity,
            $this->periodEnd
        );

        $this->chargedDtos[] = $chargedDto;
    }

    private function markOrderBilled(array $shipment): void
    {
        $this->billedOrderIds[] = $shipment['order_id'];
    }

    private function markPackageOrderItemBilled(stdClass $shippedOrderItem): void
    {
        $this->billedPackageOrderItemIds[] = $shippedOrderItem->packageOrderItemId;
    }

    private function composeDescription(array $shipment, string $suffix): string
    {
        $trackingNumber = $shipment['shipment_tracking_number'] ?: 'generic';

        return 'Order: ' . $shipment['order_number'] . ', TN: ' . $trackingNumber . ' | ' . $suffix;
    }

    private function moreThanOneSKUs(): bool
    {
        return count($this->originalShippedOrderItems) > 1;
    }

    private function sameAmountOfSKUs(array $currentShippedOrderItems): bool
    {
        return count($this->originalShippedOrderItems) == count($currentShippedOrderItems);
    }
}
