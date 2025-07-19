<?php

namespace App\Components\BillingRates\Processors;

use App\Components\BillingRates\Helpers\BillingRateCacheHelper;
use App\Models\BillingRate;
use App\Models\CacheDocuments\PackagingRateShipmentCacheDocument;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\Customer;
use App\Traits\BillingCalculatorTrait;
use App\Traits\MongoBillingCalculatorTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class PackagingBillingRateCacheProcessor
{
    use BillingCalculatorTrait, MongoBillingCalculatorTrait;

    public array $chargedDtos = [];
    public array $billingRatesCharge = [];
    public array $billedPackageOrderItemIds = [];

    public function createPackagingBillingRateCache(
        BillingRate $billingRate,
        ShipmentCacheDocument $shipmentCacheDocument,
        array $shipment,
        bool $recalculate = false
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

        Log::channel('billing')
            ->debug(
                sprintf(
                    "[Billing Rate id: %s][Shipment cache id: %s] Start creating charge cache documents for package rate",
                    $billingRate->id, $shipmentCacheDocument->id
                )
            );
        try {
            $this->billPackaging($shipment, $settings, $billingRate);
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
            Log::channel('billing')->warning(
                sprintf("[Billing Rate id: %s][Shipment cache id: %s] No creating a new rate cache for error: %s",
                    $billingRate->id, $shipmentCacheDocument->id, $errorMessage)
            );
            throw $e;
        }

        $this->addBillingRateToShipmentCacheDocument($shipmentCacheDocument, $recalculate);
        if (!empty($this->chargedDtos)) {

            $customer3pl = Customer::find($shipmentCacheDocument->get3plCustomerId());
            $documentData = PackagingRateShipmentCacheDocument::make(
                $this->chargedDtos,
                $shipment['packages'],
                $billingRate,
                $customer3pl,
                $shipmentCacheDocument->getOrder()['id'],
                $shipment
            );

            $documentData->error = $errorMessage ?? null;
            $documentData->save();
        } else {
            Log::channel('billing')->warning(
                sprintf("[Billing Rate id: %s][Shipment cache id: %s] No creating a new rate cache",
                    $billingRate->id, $shipmentCacheDocument->id
                )
            );
        }

        Log::channel('billing')->debug(
            sprintf("[Billing Rate id: %s][Shipment cache id: %s] End creating package rate cache",
            $billingRate->id, $shipmentCacheDocument->id
        ));
    }

    /**
     * @param array $shipment
     * @param array $settings
     * @param BillingRate $billingRate
     * @return void
     */
    private function billPackaging( array $shipment, array $settings, BillingRate $billingRate): void
    {
        $data = $this->filteringPackagesBySettings($shipment['packages'], $settings); // Get the values of the filtered elements

        if ($data->isNotEmpty()) {
            //check for match by package
            $data = $data->map(function ($packages) use ($settings) {
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
                        'charge' => $amount
                    ];
                });
            });

            $data = BillingRateCacheHelper::flattenArray($data->toArray()); //flat collection to a simple array

            foreach ($data as $charge) {
                $this->chargedDtos[] = $this->addChargeCacheDocumentItem(
                    $charge['description'],
                    $billingRate,
                    [
                        'fee' => $charge['charge'],
                        'shipment_id' => $shipment['id'],
                    ],
                    1
                );

                $this->addChargeCountToBillingRate($billingRate);
            }
        }

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
}
