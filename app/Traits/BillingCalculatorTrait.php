<?php

namespace App\Traits;

use App\Components\ShipmentBillingCacheService;
use App\Models\BillingRate;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use Illuminate\Support\Arr;
use LogicException;

trait BillingCalculatorTrait
{
    public function decrementQuantity(array &$shippedOrderItems, string $sku, int $index, int $quantity): void
    {
        if ($shippedOrderItems[$sku][$index]->shippedQuantity > $quantity) {
            $shippedOrderItems[$sku][$index]->shippedQuantity -= $quantity;
        } elseif ($shippedOrderItems[$sku][$index]->shippedQuantity == $quantity) {
            unset($shippedOrderItems[$sku][$index]);

            if (empty($shippedOrderItems[$sku])) {
                unset($shippedOrderItems[$sku]);
            }
        } else {
            throw new LogicException('Package order item quantity cannot be negative.');
        }
    }

    public function getSettingsFromBillingRate(BillingRate $rate): array
    {
        $settings = $rate->settings;
        $settings['charge_additional_sku_picks'] = array_key_exists('charge_additional_sku_picks', $rate->settings) ? $rate->settings['charge_additional_sku_picks'] ?? 0 : 0;
        $settings['match_has_product_tag'] = array_key_exists('match_has_product_tag', $rate->settings) ? $rate->settings['match_has_product_tag'] ?? [] : [];
        $settings['match_has_not_product_tag'] = array_key_exists('match_has_not_product_tag', $rate->settings) ? $rate->settings['match_has_not_product_tag'] ?? [] : [];
        $settings['match_has_order_tag'] = array_key_exists('match_has_order_tag', $rate->settings) ? $rate->settings['match_has_order_tag'] ?? [] : [];
        $settings['match_has_not_order_tag'] = array_key_exists('match_has_not_order_tag', $rate->settings) ? $rate->settings['match_has_not_order_tag'] ?? [] : [];
        $settings['if_no_other_rate_applies'] = Arr::get($rate->settings, 'if_no_other_rate_applies', false);
        $settings['carriers_and_methods'] = array_key_exists('carriers_and_methods', $rate->settings) ? json_decode($rate->settings['carriers_and_methods'], true) ?? [] : [];
        $settings['methods_selected'] = array_key_exists('methods_selected', $rate->settings) ? json_decode($rate->settings['methods_selected'], true) ?? [] : [];

        //package rate used
        $settings['charge_flat_fee'] = array_key_exists('charge_flat_fee', $rate->settings) ? $rate->settings['charge_flat_fee'] ?? false : false;
        $settings['flat_fee'] = array_key_exists('flat_fee', $rate->settings) ? (float)$rate->settings['flat_fee'] ?? 0.00 : 0.00;
        $settings['customer_selected'] = Arr::get($rate->settings, 'customer_selected', []);
        $settings['shipping_boxes_selected'] = array_key_exists('shipping_boxes_selected', $rate->settings) ? json_decode($rate->settings['shipping_boxes_selected'], true) ?? [] : [];
        $settings['percentage_of_cost'] = Arr::get($rate->settings, 'percentage_of_cost', null);
        $settings['percentage_of_cost'] = is_null($settings['percentage_of_cost']) ? null : (float)$settings['percentage_of_cost'];

        return $settings;
    }

    public function unsetProperties(): void
    {
        if (!empty($this->chargedDtos)) {
            unset($this->chargedDtos);
        }
        if (!empty($this->billingRatesCharge)) {
            unset($this->billingRatesCharge);
        }
        if (!empty($this->billedOrderIds)) {
            $this->billedOrderIds = [];
        }
        if (!empty($this->originalShippedOrderItems)) {
            unset($this->originalShippedOrderItems);
        }
        if (!empty($this->billedPackageOrderItemIds)) {
            $this->billedPackageOrderItemIds = [];
        }
        if (!empty($this->shipmentIds)) {
            $this->shipmentIds = [];
        }
    }

    public function addBillingRateToShipmentCacheDocument(ShipmentCacheDocument $shipmentCacheDocument, $recalculate = false): void
    {
        if(!$recalculate){
            app(ShipmentBillingCacheService::class)->updateShipmentCalculatedBillingRate(
                $shipmentCacheDocument,
                $this->billingRatesCharge
            );
        }
    }
}
