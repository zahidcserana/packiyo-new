<?php

namespace App\Components\BillingRates\PickingStategies;

use App\Components\BillingRates\Helpers\TagHelper;
use App\Features\AllowOverlappingRates;
use App\Features\FirstPickFeeFix;
use App\Models\Invoice;
use App\Models\BillingRate;
use App\Models\Shipment;
use ArrayIterator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;
use LogicException;
use stdClass;

class MysqlDataProcessingStrategy implements PickingStrategyInterface
{
    public array $billedOrderIds = [];

    /**
     * Not necessarily all units of the SKU in the package will have been billed.
     */
    public array $billedPackageOrderItemIds = [];

    public array $originalShippedOrderItems = [];

    public function calculateByRateAndInvoice(BillingRate $rate, Invoice $invoice): void
    {
        $customerId = $invoice->customer_id;
        $from = Carbon::parse($invoice->period_start);
        $to = Carbon::parse($invoice->period_end);
        $settings = $this->getSettingsFromBillingRate($rate);

        Shipment::whereHas('order', static function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('voided_at')
            ->with('packages')
            ->chunkById(1000, function ($shipments) use ($rate, $invoice, $settings) {
                DB::transaction(function () use ($rate, $invoice, $settings, $shipments) {
                    foreach ($shipments as $shipment) {
                        $this->createInvoiceLineItems($rate, $invoice, $settings, $shipment);
                    }
                });
            });

    }

    public function createInvoiceLineItems(BillingRate $rate, Invoice $invoice, array $settings, Shipment $shipment): void
    {
        $rateShouldApply = true;
        $this->loggingFees(
            $invoice,
            $rate,
            $shipment,
            $settings,
            [],
            '',
            "Start processing Shipment TK: " . $shipment->getFirstTrackingNumber() . " with Billing rate " . $rate->name
        );

        if (
            !$settings['if_no_other_rate_applies']
            && (!empty($settings['match_has_order_tag']) || !empty($settings['match_has_not_order_tag']))
        ) {
            $orderTags = $shipment->order->tags->map(static function ($tag) {
                return $tag['name'];
            })->toArray();

            $rateShouldApply = TagHelper::matchOrderTags($orderTags, $settings['match_has_order_tag'])
                && TagHelper::matchNotOrderTags($orderTags, $settings['match_has_not_order_tag']);

        }

        if (!$rateShouldApply) {
            $this->loggingFees(
                $invoice,
                $rate,
                $shipment,
                $settings,
                [],
                '',
                "BillingRate tags match shipment " . $shipment->getFirstTrackingNumber() . "  for filtering, moving to next shipment"
            );
            return;
        }

        $shippedOrderItems = $this->getShippedOrderItems($settings, $shipment);
        $this->originalShippedOrderItems = $shippedOrderItems;

        $this->chargeFlatFee($rate, $invoice, $settings, $shipment, $shippedOrderItems);
        $this->chargeFirstPickFee($invoice, $rate, $settings, $shipment, $shippedOrderItems);
        $this->chargeFirstPickOfAdditionalSku($invoice, $rate, $settings, $shipment, $shippedOrderItems);

        if (array_key_exists('pick_range_fees', $settings) && is_array($settings['pick_range_fees'])) {
            $this->chargeRangePickFees($invoice, $rate, $settings['pick_range_fees'], $shipment, $shippedOrderItems, $settings);
        }

        $this->chargeRemainingPicks($invoice, $rate, $settings, $shipment, $shippedOrderItems);
        $this->loggingFees(
            $invoice,
            $rate,
            $shipment,
            $settings,
            $shippedOrderItems,
            '',
            "Finish processing Shipment TK: " . $shipment->getFirstTrackingNumber() . " with Billing rate " . $rate->name
        );
    }


    public function getSettingsFromBillingRate(BillingRate $rate): array
    {
        $settings = $rate->settings;
        $settings['charge_additional_sku_picks'] = $rate->settings['charge_additional_sku_picks'] ?? 0;
        $settings['match_has_product_tag'] = $rate->settings['match_has_product_tag'] ?? [];
        $settings['match_has_not_product_tag'] = $rate->settings['match_has_not_product_tag'] ?? [];
        $settings['match_has_order_tag'] = $rate->settings['match_has_order_tag'] ?? [];
        $settings['match_has_not_order_tag'] = $rate->settings['match_has_not_order_tag'] ?? [];
        $settings['if_no_other_rate_applies'] = Arr::get($rate->settings, 'if_no_other_rate_applies', false);

        return $settings;
    }

    private function getShippedOrderItems(array $settings, Shipment $shipment): array
    {
        $shippedOrderItems = [];

        foreach ($shipment->packages as $package) {
            foreach ($package->packageOrderItems as $packageOrderItem) {
                if (!in_array($packageOrderItem->id, $this->billedPackageOrderItemIds)) {
                    $productTags = $packageOrderItem->orderItem->product->tags->pluck('name')->toArray();

                    if (
                        $settings['if_no_other_rate_applies']
                        || (
                            empty(array_diff($settings['match_has_product_tag'], $productTags))
                            && empty(array_intersect($productTags, $settings['match_has_not_product_tag']))
                        )
                    ) {
                        $shippedOrderItem = new stdClass();
                        $shippedOrderItem->packageOrderItemId = $packageOrderItem->id;
                        $shippedOrderItem->shippedQuantity = $packageOrderItem->quantity; // Will be decremented.
                        // $shippedOrderItem->tags = $productTags;
                        $shippedOrderItems[$packageOrderItem->orderItem->sku][] = $shippedOrderItem;
                    }
                }
            }
        }

        return $shippedOrderItems;
    }

    private function chargeFlatFee(BillingRate $rate, Invoice $invoice, array $settings, Shipment $shipment, array $shippedOrderItems): void
    {
        if (Feature::for('instance')->active(AllowOverlappingRates::class)) {
            $shouldChargeFlatFee = true;
        } else {
            $shouldChargeFlatFee = !in_array($shipment->order_id, $this->billedOrderIds);
        }

        if (
            isset($settings['charge_flat_fee'])
            && $settings['charge_flat_fee']
            && $settings['flat_fee'] > 0
            && !empty($shippedOrderItems)
            && $shouldChargeFlatFee
        ) {
            $description = 'Flat fee for order number ' . $shipment->order->number;
            $quantity = 1;
            $this->loggingFees(
                $invoice,
                $rate,
                $shipment,
                $settings,
                $shippedOrderItems,
                'flat picking fee',
                $description
            );

            $settings = ['fee' => $settings['flat_fee'], 'shipment_id' => $shipment->id];
            $this->addInvoiceLineItem($description, $invoice, $rate, $settings, $quantity);
            $this->markOrderBilled($shipment);
        }
    }

    private function chargeFirstPickFee(
        Invoice $invoice, BillingRate $rate, array $settings, Shipment $shipment, array &$shippedOrderItems
    ): void
    {
        $firstPickFee = $settings['first_pick_fee'] ?? 0.0; // always run even if not set.
        $firstPickFeeApplied = false;
        $customer = $invoice->customer->parent;

        foreach ($shippedOrderItems as $sku => $packageOrderItems) {
            if ($customer->hasFeature(FirstPickFeeFix::class) && $firstPickFeeApplied) {
                break;
            }

            $description = $this->composeDescription($shipment, 'SKU: ' . $sku . ' first pick fee');
            $this->loggingFees(
                $invoice,
                $rate,
                $shipment,
                $settings,
                $shippedOrderItems,
                'first pick fee',
                $description
            );
            $index = 0;
            $quantity = 1;

            $settingValues = [
                'fee' => $firstPickFee,
                'package_item_id' => $packageOrderItems[$index]->packageOrderItemId,
                'shipment_id' => $shipment->id
            ];
            $this->addInvoiceLineItem($description, $invoice, $rate, $settingValues, $quantity);

            $this->markPackageOrderItemBilled($packageOrderItems[$index]);
            $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
            $firstPickFeeApplied = true;
        }
    }

    private function decrementQuantity(array &$shippedOrderItems, string $sku, int $index, int $quantity): void
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

    private function markOrderBilled(Shipment $shipment): void
    {
        $this->billedOrderIds[] = $shipment->order_id;
    }

    private function markPackageOrderItemBilled(stdClass $shippedOrderItem): void
    {
        $this->billedPackageOrderItemIds[] = $shippedOrderItem->packageOrderItemId;
    }

    private function chargeFirstPickOfAdditionalSku(
        Invoice $invoice, BillingRate $rate, array $settings, Shipment $shipment, array &$shippedOrderItems
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
                    'SKU: ' . $sku . ' first pick of additional SKU'
                );

                $this->loggingFees(
                    $invoice,
                    $rate,
                    $shipment,
                    $settings,
                    $shippedOrderItems,
                    'Additional SKU fee',
                    $description
                );
                $indexes = array_keys($shippedOrderItems[$sku]); // index could be removed previously. this ensures to take the first index in the array
                $index = $indexes[0];
                $quantity = 1;
                $settingValues = [
                    'fee' => $settings['additional_sku_pick_fee'],
                    'package_item_id' => $shippedOrderItems[$sku][$index]->packageOrderItemId,
                    'shipment_id' => $shipment->id
                ];

                $this->addInvoiceLineItem($description, $invoice, $rate, $settingValues, $quantity);

                $this->markPackageOrderItemBilled($shippedOrderItems[$sku][$index]);
                $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
            }
        }
    }

    private function chargeRangePickFees(
        Invoice $invoice, BillingRate $rate, array $pickRangeFeesSetting, Shipment $shipment, array &$shippedOrderItems, array $settings
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
            Invoice $invoice,
            BillingRate $rate,
            stdClass $pickRangeFee,
            Shipment $shipment,
            int $quantity,
            array &$shippedOrderItems,
            string $sku,
            int $index,
            array $settings
        ) {
            $description = $this->composeDescription(
                $shipment,
                'SKU: ' . $sku . ' picks ' . $pickRangeFee->from . ' to ' . $pickRangeFee->to
            );
            $this->loggingFees(
                $invoice,
                $rate,
                $shipment,
                $settings,
                $shippedOrderItems,
                'Range pick fee',
                $description
            );

            $settingValues = [
                'fee' => $pickRangeFee->fee,
                'package_item_id' => $shippedOrderItems[$sku][$index]->packageOrderItemId,
                'shipment_id' => $shipment->id
            ];

            $this->addInvoiceLineItem($description, $invoice, $rate, $settingValues, $quantity);

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
                            $invoice,
                            $rate,
                            $pickRangeFee,
                            $shipment,
                            $quantity,
                            $shippedOrderItems,
                            $sku,
                            $index,
                            $settings
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
                            $invoice,
                            $rate,
                            $pickRangeFee,
                            $shipment,
                            $quantity,
                            $shippedOrderItems,
                            $sku,
                            $index,
                            $settings
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
        Invoice $invoice, BillingRate $rate, array $settings, Shipment $shipment, array &$shippedOrderItems
    ): void
    {
        $remainingItemsFee = $settings['remaining_picks_fee'];

        if ($remainingItemsFee === 0) {
            return;
        }

        foreach ($shippedOrderItems as $sku => $packageOrderItems) {
            foreach ($packageOrderItems as $index => $shippedOrderItem) {
                $description = $this->composeDescription($shipment, 'SKU: ' . $sku . ' remaining picks');
                $quantity = $shippedOrderItem->shippedQuantity;

                $this->loggingFees(
                    $invoice,
                    $rate,
                    $shipment,
                    $settings,
                    $shippedOrderItems,
                    'Remaining picks fee',
                    $description
                );
                $settingValues = [
                    'fee' => $remainingItemsFee,
                    'package_item_id' => $shippedOrderItem->packageOrderItemId,
                    'shipment_id' => $shipment->id
                ];

                $this->addInvoiceLineItem($description, $invoice, $rate, $settingValues, $quantity);
                $this->markPackageOrderItemBilled($shippedOrderItem);
                $this->decrementQuantity($shippedOrderItems, $sku, $index, $quantity);
            }
        }
    }

    private function composeDescription(Shipment $shipment, string $suffix): string
    {
        $trackingNumber = $shipment->getFirstTrackingNumber() ?: 'generic';

        return 'Order: ' . $shipment->order->number . ', TN: ' . $trackingNumber . ' | ' . $suffix;
    }

    private function moreThanOneSKUs(): bool
    {
        return count($this->originalShippedOrderItems) > 1;
    }

    private function sameAmountOfSKUs(array $currentShippedOrderItems): bool
    {
        return count($this->originalShippedOrderItems) == count($currentShippedOrderItems);
    }

    /**
     * @param string $description
     * @param Invoice $invoice
     * @param BillingRate $rate
     * @param array $settings
     * @param int $quantity
     * @return void
     */
    private function addInvoiceLineItem(
        string $description,
        Invoice $invoice,
        BillingRate $rate,
        array $settings,
        int $quantity
    ): void
    {
        app('invoice')->createInvoiceLineItem(
            $description,
            $invoice,
            $rate,
            $settings,
            $quantity,
            $invoice->period_end);
    }

    private function loggingFees(
        Invoice $invoice,
        BillingRate $rate,
        Shipment $shipment,
        array $settings,
        array $shippedOrderItems,
        ?string $fee = '',
        ?string $description = '',
    ): void
    {
        $messageTags = sprintf(
            "[Invoice id: %s][Rate Id: %s][Order number: %s][Shipment Id: %s],[tracking number: %s]",
            $invoice->id,
            $rate->id,
            $shipment->order->number,
            $shipment->id,
            $shipment->getFirstTrackingNumber()
        );

        if (!empty($fee) && !empty($description)) {
            $message = sprintf("%s: %s", $fee, $description);
        } elseif (!empty($description)) {
            $message = $description;
        } else {
            $message = '';
        }

        $messageStatus = sprintf("rate settings: %s , shipped items: %s", json_encode($settings), json_encode($shippedOrderItems));

        Log::channel('billing')->info($messageTags . ', ' . $message . ', ' . $messageStatus);
    }
}
