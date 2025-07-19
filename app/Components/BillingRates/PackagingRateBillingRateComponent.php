<?php

namespace App\Components\BillingRates;

use App\Components\BillingRates\Helpers\TagHelper;
use App\Exceptions\BillingRateException;
use App\Models\BillingRate;
use App\Models\Invoice;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class PackagingRateBillingRateComponent implements BillingRateInterface
{
    public static $rateType = BillingRate::PACKAGING_RATE;

    public function calculate(BillingRate $rate, Invoice $invoice): void
    {
        if ($rate->type != $this::$rateType) {
            return;
        }

        Log::channel('billing')->info('[BillingRate] Start ' . $this::$rateType);
        $settings = $this->getSettings($rate);

        Shipment::whereHas('order', static function (Builder $query) use ($invoice) {
            $query->where('customer_id', $invoice->customer_id);
        })
            ->whereBetween('created_at', [$invoice->period_start, $invoice->period_end])
            ->whereNull('voided_at')
            ->chunkById(100, function (&$shipments) use ($invoice, $rate, $settings) {
                foreach ($shipments as $shipment) {
                    $this->calculateForBillable($invoice, $rate, $settings, $shipment);
                }
            });
        Log::channel('billing')->info('[BillingRate] End ' . $this::$rateType);
    }

    /**
     * @throws BillingRateException
     */
    public function calculateForBillable(Invoice $invoice, BillingRate $rate, array $settings, Shipment $shipment): void
    {
        $rateShouldApply = $this->rateShouldApplies($settings, $shipment);

        if (!$rateShouldApply) {
            return;
        }

        try {
            $this->billPackaging($shipment, $settings, $rate, $invoice);
        } catch (Throwable $e) {
            throw new BillingRateException($rate, $e);
        }
    }

    public function tracksBilledOperations(): bool
    {
        return false;
    }

    public function resetBilledOperations(): void
    {
        // TODO: Implement resetBilledOperations() method.
    }

    public function getSettings(BillingRate $rate): array
    {
        $settings = $rate->settings;
        $settings['if_no_other_rate_applies'] = Arr::get($rate->settings, 'if_no_other_rate_applies', false);
        $settings['match_has_product_tag'] = array_key_exists('match_has_product_tag', $rate->settings) ? $rate->settings['match_has_product_tag'] ?? [] : [];
        $settings['match_has_not_product_tag'] = array_key_exists('match_has_not_product_tag', $rate->settings) ? $rate->settings['match_has_not_product_tag'] ?? [] : [];
        $settings['match_has_order_tag'] = array_key_exists('match_has_order_tag', $rate->settings) ? $rate->settings['match_has_order_tag'] ?? [] : [];
        $settings['match_has_not_order_tag'] = array_key_exists('match_has_not_order_tag', $rate->settings) ? $rate->settings['match_has_not_order_tag'] ?? [] : [];
        $settings['charge_flat_fee'] = array_key_exists('charge_flat_fee', $rate->settings) ? $rate->settings['charge_flat_fee'] ?? false : false;
        $settings['flat_fee'] = array_key_exists('flat_fee', $rate->settings) ? (float)$rate->settings['flat_fee'] ?? 0.00 : 0.00;
        $settings['customer_selected'] = Arr::get($rate->settings, 'customer_selected', []);
        $settings['shipping_boxes_selected'] = array_key_exists('shipping_boxes_selected', $rate->settings) ? json_decode($rate->settings['shipping_boxes_selected'], true) ?? [] : [];
        $settings['percentage_of_cost'] = Arr::get($rate->settings, 'percentage_of_cost', null);
        $settings['percentage_of_cost'] = is_null($settings['percentage_of_cost']) ? null : (float)$settings['percentage_of_cost'];

        return $settings;
    }

    private function rateShouldApplies(array $settings, Shipment $shipment): bool
    {
        $rateShouldApply = true;
        if (
            !$settings['if_no_other_rate_applies']
            && (!empty($settings['match_has_order_tag']) || !empty($settings['match_has_not_order_tag']))
        ) {
            $rateShouldApply = $this->matchesByOrderTag($settings, $shipment);
        }
        return $rateShouldApply;
    }

    protected function matchesByOrderTag(array $settings, Shipment $shipment): bool
    {
        if (!empty($settings['match_has_order_tag']) || !empty($settings['match_has_not_order_tag'])) {
            $tagNames = $shipment->order->tags->map(static function ($tag) {
                return $tag->name;
            })->toArray();

            return TagHelper::matchOrderTags($tagNames, $settings['match_has_order_tag'])
                && TagHelper::matchNotOrderTags($tagNames, $settings['match_has_not_order_tag']);
        }

        return true;
    }

    private function billPackaging(Shipment $shipment, array $settings, BillingRate $billingRate, Invoice $invoice): void
    {
        $packages = $this->getPackagesByShipment($shipment);
        $filterPackages = $this->filteringPackagesBySettings($packages, $settings);

        if ($filterPackages->isNotEmpty()) {
            $charges = $this->buildChargesForPackages($filterPackages, $settings);
            $charges = $this->flattenArray($charges->toArray()); //flat collection to a simple array

            foreach ($charges as $charge) {
                $this->addItemInvoiceLineItem(
                    $charge['description'],
                    $invoice,
                    $billingRate,
                    [
                        'fee' => $charge['charge'],
                        'shipment_id' => $shipment->id,
                        'package_id' => $charge['package_id']
                    ]
                );
            }
        }
    }

    public function addItemInvoiceLineItem(
        string $description,
        Invoice $invoice,
        BillingRate $billingRate,
        array $settings
    ): void
    {
        app('invoice')->createInvoiceLineItem(
            $description,
            $invoice,
            $billingRate,
            $settings,
            1, // Quantity, one charge per shipment.
            $invoice->period_end
        );
    }

    /**
     * @param $packages
     * @param array $settings
     * @return Collection
     */
    private function filteringPackagesBySettings($packages, array $settings): Collection
    {
        if (!empty($settings['shipping_boxes_selected'])) {
            return collect($packages)->filter(function ($packageItem) use ($settings) {
                return collect($packageItem)->filter(function ($package) use ($settings) {
                    $shippingBoxFound = false;
                    // Shipping Boxes
                    foreach ($settings['shipping_boxes_selected'] as $shippingBoxes) {
                        if (in_array($package['shipping_box']['id'], $shippingBoxes)) {
                            $shippingBoxFound = true;
                            break;
                        }
                    }
                    return $shippingBoxFound;
                })->isNotEmpty(); // Check if the filtered collection is not empty
            })->values();
        }

        return collect($packages);
    }

    /**
     * @param float $percentage_of_cost
     * @param float $cost
     * @return float
     */
    function calculateCostByPercentage(float $percentage_of_cost, float $cost): float
    {
        return $cost == 0.00 ? 0.00 : (($percentage_of_cost * $cost) / 100);
    }

    private function flattenArray(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Check if the current depth is the last depth
                if (!empty($value) && is_array(reset($value))) {
                    // If it is, recursively flatten the array
                    $result = array_merge($result, $this->flattenArray($value));
                } else {
                    // Otherwise, add the key and value to the result
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * @param Shipment $shipment
     * @return array
     */
    private function getPackagesByShipment(Shipment $shipment): array
    {
        return $shipment->packages->map(function ($package) {
            return $package->packageOrderItems->map(function ($packageOrderItem) use ($package) {
                return [
                    'package_id' => $package->id,
                    'package_order_item_id' => $packageOrderItem->id,
                    'order_item' => [
                        'id' => $packageOrderItem->orderItem->id,
                        'product_id' => $packageOrderItem->orderItem->product->id,
                        'productTagsName' => $packageOrderItem->orderItem->product->tags->pluck('name')->toArray(),
                        'quantity' => $packageOrderItem->orderItem->quantity,
                        'sku' => $packageOrderItem->orderItem->sku
                    ],
                    'shipping_box' => [
                        'id' => $package->shippingBox->id,
                        'name' => $package->shippingBox->name,
                        'length' => $package->shippingBox->length,
                        'barcode' => $package->shippingBox->barcode,
                        'width' => $package->shippingBox->width,
                        'height' => $package->shippingBox->height,
                        'cost' => $package->shippingBox->getCost(),
                    ]
                ];
            });
        })->toArray();
    }

    /**
     * @param Collection $filterPackages
     * @param array $settings
     * @return Collection
     */
    private function buildChargesForPackages(Collection $filterPackages, array $settings): Collection
    {
        return $filterPackages->map(function ($packages) use ($settings) {
            return collect($packages)->map(function ($package) use ($settings) {
                $amount = 0.0;

                if ($settings['charge_flat_fee']) {
                    $amount += $settings['flat_fee'];
                }

                if (!empty($package['shipping_box']['cost'])) {
                    $amount += $this->calculateCostByPercentage(
                        $settings['percentage_of_cost'],
                        (float) $package['shipping_box']['cost']
                    );
                }

                return [
                    'shipping_box_name' => $package['shipping_box']['name'],
                    'description' => sprintf('Charge for box name: %s', $package['shipping_box']['name']),
                    'charge' => $amount,
                    'package_id' => $package['package_id']
                ];
            });
        });
    }
}
