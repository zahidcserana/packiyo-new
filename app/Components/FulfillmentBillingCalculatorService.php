<?php

namespace App\Components;

use App\Components\BillingRates\Processors\PackagingBillingRateCacheProcessor;
use App\Components\BillingRates\Processors\PickingBillingRateCacheProcessor;
use App\Components\BillingRates\Processors\ShippingBillingRateCacheProcessor;
use App\Exceptions\BillingRateException;
use App\Models\BillingRate;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Traits\BillingCalculatorTrait;
use App\Traits\MongoBillingCalculatorTrait;
use Illuminate\Support\Facades\Log;
use Throwable;

class FulfillmentBillingCalculatorService
{
    use BillingCalculatorTrait, MongoBillingCalculatorTrait;

    public function __construct(
        private readonly PickingBillingRateCacheProcessor $pickingProcessor,
        private readonly ShippingBillingRateCacheProcessor $shippingProcessor,
        private readonly PackagingBillingRateCacheProcessor $packageProcessor
    )
    {
    }

    /**
     * @throws BillingRateException
     */
    public function calculate(
        ShipmentCacheDocument $shipmentCacheDocument,
        BillingRate $billingRate,
        bool $recalculate = false
    ): void
    {
        try {
            Log::channel('billing')->info(sprintf("[Fulfillment Calculator] Start creating document id: %s for charges for billing rate id: %s" , $shipmentCacheDocument->id, $billingRate->id));
            $shipments = $shipmentCacheDocument->getShipments();
            switch ($billingRate->type) {
                case BillingRate::SHIPMENTS_BY_PICKING_RATE_V2:
                    $hasMultipleShipments = count($shipments) > 1;
                    $orderAlreadyBilled = false;
                    foreach ($shipments as $shipment) {
                        $this->pickingProcessor->createPickingBillingRateCache(
                            $billingRate,
                            $shipmentCacheDocument,
                            $shipment,
                            $recalculate,
                            $orderAlreadyBilled
                        );
                        if ($hasMultipleShipments){
                            $orderAlreadyBilled = true;
                        }
                    }
                    break;
                case BillingRate::SHIPMENTS_BY_SHIPPING_LABEL:
                    foreach ($shipments as $shipment) {
                        $this->shippingProcessor->createShippingBillingRateCache(
                            $billingRate,
                            $shipmentCacheDocument,
                            $shipment,
                            $recalculate,
                        );
                    }
                    break;
                case BillingRate::PACKAGING_RATE:
                    foreach ($shipments as &$shipment) {
                        $this->packageProcessor->createPackagingBillingRateCache(
                            $billingRate,
                            $shipmentCacheDocument,
                            $shipment,
                            $recalculate
                        );
                    }
                    break;
                default:
                    //todo not implemented BILLING RATE TYPE
            }
        } catch (Throwable $exception) {
            $newException = new BillingRateException($billingRate, $exception);
            Log::debug(sprintf('[Fulfillment Calculator] Fulfillment charge fail, Error: %s', $newException->getMessage()));
            throw $newException;
        }
        // Continue here for next rates

        Log::channel('billing')->info(sprintf("[Fulfillment Calculator] End generating charges document id: %s for charges for billing rate id: %s", $shipmentCacheDocument->id, $billingRate->id));
    }
}
