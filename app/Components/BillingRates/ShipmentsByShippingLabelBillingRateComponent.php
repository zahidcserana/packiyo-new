<?php

namespace App\Components\BillingRates;

use App\Components\BillingRates\Helpers\SlugComparerHelper;
use App\Components\BillingRates\Helpers\TagHelper;
use App\Models\Shipment;
use App\Models\BillingRate;
use App\Models\Invoice;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Traits\ShippingCalculatorTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
class ShipmentsByShippingLabelBillingRateComponent implements BillingRateInterface
{
    use ShippingCalculatorTrait;
    public static string $rateType = 'shipments_by_shipping_label';
    public array $shipmentIds = [];

    public function tracksBilledOperations(): bool
    {
        return true;
    }

    public function resetBilledOperations(): void
    {
        $this->shipmentIds = [];
    }

    public function calculate(BillingRate $rate, Invoice $invoice): void
    {
        if ($rate->type != $this::$rateType) {
            return;  // TODO: Maybe throw an error here? Why is this a silent no-op?
        }

        Log::channel('billing')->info('[BillingRate] Start ' . $this::$rateType);

        $settings = $this->getSettings($rate);

        Shipment::whereHas('order', static function (Builder $query) use ($invoice) {
            $query->where('customer_id', $invoice->customer_id);
        })
            ->whereBetween('created_at', [$invoice->period_start, $invoice->period_end])
            ->whereNull('voided_at')
            ->chunkById(1000, function (&$shipments) use ($invoice, $rate, $settings) {
                foreach ($shipments as $shipment) {
                    $this->calculateForBillable($invoice, $rate, $settings, $shipment);
                }
            });

        Log::channel('billing')->info('[BillingRate] End ' . $this::$rateType);
    }

    protected function getSettings(BillingRate $rate): array
    {
        $settings = $rate->settings;
        $settings['methods_selected'] = $this->getSelectedShippingMethods($settings);
        $settings['match_has_order_tag'] = $settings['match_has_order_tag'] ?? [];
        $settings['match_has_not_order_tag'] = $settings['match_has_not_order_tag'] ?? [];
        $settings['if_no_other_rate_applies'] = Arr::get($rate->settings, 'if_no_other_rate_applies', false);
        $settings['carriers_and_methods'] = json_decode($settings['carriers_and_methods'], true) ?? [];

        return $settings;
    }

    protected function getSelectedShippingMethods(array $settings): array
    {
        return array_filter(json_decode($settings['methods_selected'], true), function (array $shippingMethods) {
            return !empty($shippingMethods);
        });
    }

    private function calculateForBillable(Invoice $invoice, BillingRate $rate, array $settings, Shipment $shipment)
    {
        if ($shipment->isGeneric()) {
            return;
        }

        if (
            $this->matchesIfDefault($settings, $shipment)
            || (
                $this->matchesByOrderTag($settings, $shipment) && (
                    $this->matchesByCarrier($settings, $shipment->shippingMethod->shippingCarrier->id) ||
                    $this->matchesByShippingMethod($settings, $shipment->shippingMethod->id)
                )
            )
        ) {
            $this->billShipment($shipment, $settings, $invoice, $rate);
        }
    }

    protected function matchesIfDefault(array $settings, Shipment $shipment): bool
    {
        if (!$settings['if_no_other_rate_applies']) {
            return false;
        }

        return !in_array($shipment->id, $this->shipmentIds);
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

    private function matchesByCarrier(array $settings, int $shipmentCarrierId): bool
    {
        $carriersAndMethods = $settings['carriers_and_methods'] ?? [];

        if (count($carriersAndMethods) === 0) {
            return false;
        }

        return collect($carriersAndMethods)->some(function ($configuredCarrierId) use ($shipmentCarrierId) {
            return SlugComparerHelper::compareByClass(ShippingCarrier::class, $configuredCarrierId, $shipmentCarrierId);
        });
    }

    private function matchesByShippingMethod(array $rateSetting, int $shippingMethodId): bool
    {
        $methodsSelected = $rateSetting['methods_selected'] ?? [];

        if (count($methodsSelected) === 0) {
            return true;
        }

        return collect($methodsSelected)
            ->some(function (array $selectedMethods) use ($shippingMethodId) {
                return collect($selectedMethods)
                    ->some(function (int $selectedMethodId) use ($shippingMethodId) {
                        return SlugComparerHelper::compareByClass(ShippingMethod::class, $selectedMethodId, $shippingMethodId);
                    });
            });
    }

    public function billShipment(Shipment $shipment, array $settings, Invoice $invoice, BillingRate $rate): void
    {
        $baseShippingCost = Arr::get($settings, 'charge_flat_fee') ? $settings['flat_fee'] : 0;
        $percentageOfCost = $settings['percentage_of_cost'] / 100;
        $total = $baseShippingCost + ($shipment->cost * $percentageOfCost);
        $description = $this->composeItemDescription($shipment, $total, $baseShippingCost, $percentageOfCost);

        Log::channel('billing')->debug("Invoice line item added: ". $description);
        app('invoice')->createInvoiceLineItem(
            $description,
            $invoice,
            $rate,
            [
                'fee' => $total,
                'shipment_id' => $shipment->id
            ],
            1, // Quantity, one charge per shipment.
            $invoice->period_end
        );

        $this->shipmentIds[] = $shipment->id;
    }

    protected function composeItemDescription(Shipment $shipment, float $total, float $baseShippingCost, float $percentageOfCost)
    {
        if ($shipment->shippingMethod) {
            $carrierName = $shipment->shippingMethod->shippingCarrier->name;
            $shippingMethodName = $shipment->shippingMethod->name;
        } else {
            $carrierName = 'unknown';
            $shippingMethodName = 'unknown';
        }

        return 'Shipment Number: ' . $shipment->getFirstTrackingNumber()
            . ' | ' . $carrierName . ' via ' . $shippingMethodName
            . ', order no. ' . $shipment->order->number;
    }
}
