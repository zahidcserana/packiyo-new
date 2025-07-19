<?php

namespace App\Components\BillingRates\Processors;

use App\Features\FirstPickFeeFix;
use App\Models\BillingRate;
use App\Models\CacheDocuments\PickingBillingRateShipmentCacheDocument;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\Customer;
use App\Traits\BillingCalculatorTrait;
use App\Traits\MongoBillingCalculatorTrait;
use ArrayIterator;
use Illuminate\Support\Facades\Log;
use LogicException;
use stdClass;

class PickingBillingRateCacheProcessor
{
    use BillingCalculatorTrait, MongoBillingCalculatorTrait;

    public array $billedOrderIds = [];
    public array $billedPackageOrderItemIds = [];
    public array $originalShippedOrderItems = [];
    public array $chargedDtos = [];
    public array $billingRatesCharge = [];

    public function createPickingBillingRateCache(
        BillingRate $billingRate,
        ShipmentCacheDocument $shipmentCacheDocument,
        array $shipment,
        bool $recalculate = false,
        bool $orderAlreadyBilled = false,
    ): void
    {
        $this->unsetProperties();
        $this->billingRatesCharge[] = $this->addBillingRate($billingRate);
        $settings = $this->getSettingsFromBillingRate($billingRate);

        $rateShouldApply = $this->rateApplies($settings, $shipmentCacheDocument);

        if (!$rateShouldApply) {
            $this->addBillingRateToShipmentCacheDocument($shipmentCacheDocument, $recalculate);
            return;
        }

        $customer = Customer::find($shipmentCacheDocument->getOrderCustomer());
        $shippedOrderItems = $this->getShippedOrderItems($settings, $shipment);
        $this->setOriginalOrderItems($shippedOrderItems);
        $order = $shipmentCacheDocument->getOrder();
        try {

            $this->chargeFlatFee($billingRate, $settings, $shipment, $shippedOrderItems, $order, $orderAlreadyBilled);
            $this->chargeFirstPickFee($billingRate, $settings, $shipment, $shippedOrderItems, $order['number'], $customer);
            $this->chargeFirstPickOfAdditionalSku($billingRate, $settings, $shipment, $shippedOrderItems, $order['number']);

            if (array_key_exists('pick_range_fees', $settings) && is_array($settings['pick_range_fees'])) {
                $this->chargeRangePickFees($billingRate, $settings['pick_range_fees'], $shipment, $shippedOrderItems, $order['number']);
            }
            $this->chargeRemainingPicks($billingRate, $settings, $shipment, $shippedOrderItems, $order['number']);
        } catch (\Exception $exception) {
            if (!empty($this->chargedDtos)) {
                $errorMessage = $exception->getMessage(); //if error occur during execution save in here.
            }
        }

        $this->addBillingRateToShipmentCacheDocument($shipmentCacheDocument, $recalculate);

        $cacheDocumentData = PickingBillingRateShipmentCacheDocument::make(
            $this->originalShippedOrderItems,
            $this->chargedDtos,
            $billingRate,
            $customer,
            $order['id'],
            $shipment
        );
        $cacheDocumentData->error = $errorMessage ?? null;

        $cacheDocumentData->save();
    }

    private function chargeFlatFee(
        BillingRate $rate,
        array $settings,
        array $shipment,
        array $shippedOrderItems,
        array $order,
        bool $orderAlreadyBilled = false
    ): void
    {
        if (
            isset($settings['charge_flat_fee'])
            && $settings['charge_flat_fee']
            && $settings['flat_fee'] > 0
            && !empty($shippedOrderItems)
            && !$orderAlreadyBilled
        ) {
            $description = 'Flat fee for order number ' . $order['number'];
            $quantity = 1;

            $settings = ['fee' => $settings['flat_fee'], 'shipment_id' => $shipment['id']];
            $this->chargedDtos[] = $this->addChargeCacheDocumentItem(
                $description,
                $rate,
                $settings,
                $quantity
            );

            $this->addChargeCountToBillingRate($rate);
            $this->markOrderBilled($order);
        }
    }

    private function chargeFirstPickFee(
        BillingRate $rate,
        array $settings,
        array $shipment,
        array &$shippedOrderItems,
        string $orderNumber,
        Customer $customer
    ): void
    {
        $firstPickFee = $settings['first_pick_fee'] ?? 0.0; // always run even if not set.
        $firstPickFeeApplied = false;

        foreach ($shippedOrderItems as $sku => $packageOrderItems) {
            if ($customer->parent->hasFeature(FirstPickFeeFix::class) && $firstPickFeeApplied) {
                break;
            }

            $description = $this->composeDescription($shipment, 'SKU: ' . $sku . ' first pick fee', $orderNumber);
            Log::channel('billing')->info('[BillingRate] first pick fee' . $description);
            $index = 0;
            $quantity = 1;

            $settingValues = [
                'fee' => $firstPickFee,
                'package_item_id' => $packageOrderItems[$index]->packageOrderItemId,
                'shipment_id' => $shipment['id']
            ];

            $this->chargedDtos[] = $this->addChargeCacheDocumentItem(
                $description,
                $rate,
                $settingValues,
                $quantity
            );

            $this->markPackageOrderItemBilled($packageOrderItems[$index]);
            $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
            $this->addChargeCountToBillingRate($rate);
            $firstPickFeeApplied = true;
        }
    }

    private function chargeFirstPickOfAdditionalSku(
        BillingRate $rate,
        array $settings,
        array $shipment,
        array &$shippedOrderItems,
        string $orderNumber
    ): void
    {
        if (!$this->moreThanOneSKUs() || empty($shippedOrderItems)) {
            return;
        }

        if ($settings['charge_additional_sku_picks']) {

            $additionalSkus = array_slice(array_keys($shippedOrderItems), $this->sameAmountOfSKUs($shippedOrderItems) ? 1 : 0);

            foreach ($additionalSkus as $sku) {
                $description = $this->composeDescription(
                    $shipment,
                    'SKU: ' . $sku . ' first pick of additional SKU',
                    $orderNumber
                );

                Log::channel('billing')->info('[BillingRate] Additional SKU fee' . $description);
                $index = 0;
                $quantity = 1;
                $settingValues = [
                    'fee' => $settings['additional_sku_pick_fee'],
                    'package_item_id' => $shippedOrderItems[$sku][$index]->packageOrderItemId,
                    'shipment_id' => $shipment['id']
                ];
                $this->chargedDtos[] = $this->addChargeCacheDocumentItem(
                    $description,
                    $rate,
                    $settingValues,
                    $quantity
                );

                $this->addChargeCountToBillingRate($rate);
                $this->markPackageOrderItemBilled($shippedOrderItems[$sku][$index]);
                $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
            }
        }
    }

    private function chargeRangePickFees(
        BillingRate $rate,
        array $pickRangeFeesSetting,
        array $shipment,
        array &$shippedOrderItems,
        string $orderNumber
    ): void
    {
        $pickRangeFees = [];
        $from = 2;

        foreach ($pickRangeFeesSetting as $pickRangeFeeData) {

            $pickRangeFee = (object)$pickRangeFeeData;
            if (!empty($pickRangeFeeData['from'])) {

                $pickRangeFee->from = $from;
                $pickRangeFee->amount = $pickRangeFeeData['to'] - $from + 1;
            } else {
                $pickRangeFee->from = $pickRangeFeeData['from'];
                $pickRangeFee->amount = $pickRangeFeeData['to'] - $pickRangeFeeData['from'] + 1;
            }
            $from = $pickRangeFee->to + 1;
            $pickRangeFees[] = $pickRangeFee;
        }

        $pickRangeFees = new ArrayIterator($pickRangeFees);

        $createInvoiceLineItem = function (
            BillingRate $rate,
            stdClass $pickRangeFee,
            array $shipment,
            int $quantity,
            array &$shippedOrderItems,
            string $sku,
            int $index,
            string $orderNumber
        ) {
            $description = $this->composeDescription(
                $shipment,
                'SKU: ' . $sku . ' picks ' . $pickRangeFee->from . ' to ' . $pickRangeFee->to,
                $orderNumber
            );
            Log::channel('billing')->info('[BillingRate] Range pick fee ' . $description);

            $settingValues = [
                'fee' => $pickRangeFee->fee,
                'package_item_id' => $shippedOrderItems[$sku][$index]->packageOrderItemId,
                'shipment_id' => $shipment['id']
            ];

            $this->chargedDtos[] = $this->addChargeCacheDocumentItem(
                $description,
                $rate,
                $settingValues,
                $quantity
            );

            $this->addChargeCountToBillingRate($rate);
            $this->markPackageOrderItemBilled($shippedOrderItems[$sku][$index]);
            $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
        };

        foreach ($shippedOrderItems as $sku => &$packageOrderItems) {
            foreach ($packageOrderItems as $index => &$shippedOrderItem) {
                while ($pickRangeFees->valid()) {
                    if (!isset($shippedOrderItems[$sku][$index])) {
                        break;
                    }
                    $pickRangeFee = $pickRangeFees->current();

                    if ($pickRangeFee->amount <= $shippedOrderItem->shippedQuantity) {
                        if (!array_key_exists($sku, $shippedOrderItems)) {
                            // skip if sku is not found in orderItems
                            break;
                        }

                        $quantity = $pickRangeFee->amount; // Charge for all and we're done with this fee.
                        $pickRangeFee->amount = 0;
                        $createInvoiceLineItem(
                            $rate,
                            $pickRangeFee,
                            $shipment,
                            $quantity,
                            $shippedOrderItems,
                            $sku,
                            $index,
                            $orderNumber
                        );
                        $pickRangeFees->next();
                        // continue; // For clarity's sake.
                    } elseif ($pickRangeFee->amount > $shippedOrderItem->shippedQuantity) {
                        if (!array_key_exists($sku, $shippedOrderItems)) {
                            // skip if sku is not found in orderItems
                            break;
                        }
                        $quantity = $shippedOrderItem->shippedQuantity; // Charge what was shipped.
                        $pickRangeFee->amount -= $quantity;
                        $createInvoiceLineItem(
                            $rate,
                            $pickRangeFee,
                            $shipment,
                            $quantity,
                            $shippedOrderItems,
                            $sku,
                            $index,
                            $orderNumber
                        );
                        continue 2; // Stay on this fee, change shipped order item.
                    } else {
                        throw new LogicException('This should be unreachable.');
                    }
                }
            }
        }
    }

    private function chargeRemainingPicks(
        BillingRate $rate,
        array $settings,
        array $shipment,
        array &$shippedOrderItems,
        string $orderNumber
    ): void
    {
        $remainingItemsFee = $settings['remaining_picks_fee'];

        if ($remainingItemsFee === 0) {
            return;
        }

        foreach ($shippedOrderItems as $sku => $packageOrderItems) {
            foreach ($packageOrderItems as $index => $shippedOrderItem) {
                $description = $this->composeDescription($shipment, 'SKU: ' . $sku . ' remaining picks', $orderNumber);
                $quantity = $shippedOrderItem->shippedQuantity;

                Log::channel('billing')->info('[BillingRate] remaining picks fee' . $description);

                $settingValues = [
                    'fee' => $remainingItemsFee,
                    'package_item_id' => $shippedOrderItem->packageOrderItemId,
                    'shipment_id' => $shipment['id']
                ];

                $this->chargedDtos[] = $this->addChargeCacheDocumentItem(
                    $description,
                    $rate,
                    $settingValues,
                    $quantity
                );

                $this->addChargeCountToBillingRate($rate);
                $this->markPackageOrderItemBilled($shippedOrderItem);
                $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
            }
        }
    }


    private function markOrderBilled(array $order): void
    {
        $this->billedOrderIds[] = $order['id'];
    }

    private function markPackageOrderItemBilled(stdClass $shippedOrderItem): void
    {
        $this->billedPackageOrderItemIds[] = $shippedOrderItem->packageOrderItemId;
    }

    private function moreThanOneSKUs(): bool
    {
        return count($this->originalShippedOrderItems) > 1;
    }

    private function sameAmountOfSKUs(array $currentShippedOrderItems): bool
    {
        return count($this->originalShippedOrderItems) == count($currentShippedOrderItems);
    }

    private function setOriginalOrderItems(array $items): void
    {
        $clonedArray = [];

        foreach ($items as $key => $originalObject) {
            $clonedArray[$key] = json_decode(json_encode($originalObject), true);
        }
        $this->originalShippedOrderItems = $clonedArray;
    }
}
