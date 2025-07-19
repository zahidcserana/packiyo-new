<?php

namespace App\Components\BillingRates\Processors;

use App\Models\BillingRate;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\CacheDocuments\ShippingLabelRateShipmentCacheDocument;
use App\Models\Customer;
use App\Traits\BillingCalculatorTrait;
use App\Traits\MongoBillingCalculatorTrait;
use App\Traits\ShippingCalculatorTrait;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class ShippingBillingRateCacheProcessor
{
    use BillingCalculatorTrait, MongoBillingCalculatorTrait, ShippingCalculatorTrait;
    public array $billedPackageOrderItemIds = [];
    public array $chargedDtos = [];
    public array $shipmentIds = [];
    public array $billingRatesCharge = [];

    public function createShippingBillingRateCache(
        BillingRate $billingRate,
        ShipmentCacheDocument $shipmentCacheDocument,
        array $shipment,
        bool $recalculate = false
    ): void
    {
        $this->unsetProperties();
        $this->billingRatesCharge[] = $this->addBillingRate($billingRate);
        if (empty($shipmentCacheDocument->getShippingMethod())) {
            $this->addBillingRateToShipmentCacheDocument($shipmentCacheDocument, $recalculate);
            return;
        }

        if($shipment['isGeneric']){
            return;
        }

        $settings = $this->getSettingsFromBillingRate($billingRate);
        $rateShouldApply = $this->rateApplies($settings, $shipmentCacheDocument);

        if (!$rateShouldApply) {
            $this->addBillingRateToShipmentCacheDocument($shipmentCacheDocument, $recalculate);
            return;
        }

        if (
            $this->matchesIfDefault($settings, $shipment) || $this->matchesByShipment($settings, $shipmentCacheDocument)
        ) {
            try {
                $this->billShipment($shipmentCacheDocument, $shipment, $settings, $billingRate);
            } catch (\Exception $exception) {
                if (!empty($this->chargedDtos)) {
                    $errorMessage = $exception->getMessage(); //if error occur during execution save in here.
                }
            }

            $this->addBillingRateToShipmentCacheDocument($shipmentCacheDocument, $recalculate);

            $customer = Customer::find($shipmentCacheDocument->getOrderCustomer());
            $documentData = ShippingLabelRateShipmentCacheDocument::make(
                $this->chargedDtos,
                $shipment,
                $billingRate,
                $customer,
                $shipmentCacheDocument->getOrder()['id']
            );
            $documentData->error = $errorMessage ?? null;
            $documentData->save();
            return;
        }

        $this->addBillingRateToShipmentCacheDocument($shipmentCacheDocument, $recalculate);
    }

    private function matchesIfDefault(array $settings, array $shipment): bool
    {
        if (!$settings['if_no_other_rate_applies']) {
            return false;
        }

        return !in_array($shipment['id'], $this->shipmentIds);
    }

    private function billShipment(
        ShipmentCacheDocument $shipmentCacheDocument,
        array $shipment,
        array $settings,
        BillingRate $billingRate
    ): void
    {
        $baseShippingCost = Arr::get($settings, 'charge_flat_fee') ? $settings['flat_fee'] : 0;
        $percentageOfCost = $settings['percentage_of_cost'] / 100;
        $total = $baseShippingCost + ($shipment['cost'] * $percentageOfCost);
        $description = $this->composeItemDescriptionShipment(
            $shipmentCacheDocument->getShippingMethod(),
            $shipment['shipment_tracking_number'],
            $shipmentCacheDocument->getOrder()['number']
        );

        $this->chargedDtos[] = $this->addChargeCacheDocumentItem(
            $description,
            $billingRate,
            [
                'fee' => $total,
                'shipment_id' => $shipment['id']
            ],
            count($shipment['packages'])
        );
        $this->addChargeCountToBillingRate($billingRate);

        $this->shipmentIds[] = $shipment['id'];
    }


    protected function composeItemDescriptionShipment(
        array $shippingMethod,
        string $trackingNumber,
        string $order_number
    ): string
    {
        if (!empty($shippingMethod)) {
            $carrierName = $shippingMethod['shipping_carrier']['name'];
            $shippingMethodName = $shippingMethod['name'];
        } else {
            $carrierName = 'unknown';
            $shippingMethodName = 'unknown';
        }

        return 'Shipment Number: ' . $trackingNumber
            . ' | ' . $carrierName . ' via ' . $shippingMethodName
            . ', order no. ' . $order_number;
    }

    /**
     * @param array $settings
     * @param ShipmentCacheDocument $shipmentCacheDocument
     * @return bool
     */
    private function matchesByShipment(array $settings, ShipmentCacheDocument $shipmentCacheDocument): bool
    {
        return $this->matchesByCarrier($settings, $shipmentCacheDocument->getShippingMethod()['shipping_carrier']['id'])
            || $this->matchesByShippingMethod($settings, $shipmentCacheDocument->getShippingMethod()['id']);
    }
}
