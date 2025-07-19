<?php

namespace App\Traits;

use App\Models\BillingRate;
use App\Models\CacheDocuments\DataTransferObject\BillingChargeItemDto;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use Carbon\Carbon;
use stdClass;

trait MongoBillingCalculatorTrait
{
    public function composeDescription(array $shipment, string $suffix, string $orderNumber): string
    {
        $trackingNumber = $shipment['shipment_tracking_number'] ?: 'generic';

        return 'Order: ' . $orderNumber . ', TN: ' . $trackingNumber . ' | ' . $suffix;
    }

    /**
     * @param array $settings
     * @param ShipmentCacheDocument $shipment
     * @return bool
     */
    public function rateApplies(array $settings, ShipmentCacheDocument $shipment): bool
    {
        $rateShouldApply = true;

        if (
            !$settings['if_no_other_rate_applies']
            && (!empty($settings['match_has_order_tag']) || !empty($settings['match_has_not_order_tag']))
        ) {
            $orderTags = $shipment->getOrder()['tags'];

            $rateShouldApply = empty(array_diff($settings['match_has_order_tag'], $orderTags))
                && empty(array_intersect($orderTags, $settings['match_has_not_order_tag']));
        }
        return $rateShouldApply;
    }

    public function getShippedOrderItems(array $settings, array $shipment): array
    {
        $shippedOrderItems = [];

        foreach ($shipment['packages'] as $packages) {
            foreach ($packages as $package) {
                $orderItem = $package['order_item'];
                if (!in_array($package['id'], $this->billedPackageOrderItemIds)) {
                    $productTags = $orderItem['productTagsName'];

                    if (
                        $settings['if_no_other_rate_applies']
                        || (
                            empty(array_diff($settings['match_has_product_tag'], $productTags))
                            && empty(array_intersect($productTags, $settings['match_has_not_product_tag']))
                        )
                    ) {
                        $shippedOrderItem = new stdClass();
                        $shippedOrderItem->packageOrderItemId = $package['id'];
                        $shippedOrderItem->shippedQuantity = $orderItem['quantity']; // Will be decremented.
                        // $shippedOrderItem->tags = $productTags;
                        $shippedOrderItems[$orderItem['sku']][] = $shippedOrderItem;
                    }
                }
            }
        }

        return $shippedOrderItems;
    }

    public function addChargeCacheDocumentItem(
        $description,
        $rate,
        $settings,
        $quantity
    ): BillingChargeItemDto
    {
        return new BillingChargeItemDto(
            $description,
            $rate,
            $settings,
            $quantity
        );
    }

    public function addBillingRate(BillingRate $rate): array
    {
        return [
            'billing_rate_id' => $rate->id,
            'calculated_at' => $rate->updated_at->toIso8601String(),
            'charges' => 0
        ];
    }

    public function addChargeCountToBillingRate(BillingRate $rate): void
    {
        foreach ($this->billingRatesCharge as &$element) {
            if ($element['billing_rate_id'] == $rate->id) {
                $element['charges'] += 1;
            }
        }
    }
}
