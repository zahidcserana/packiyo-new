<?php

namespace App\Components;

use App\Events\OrderShippedEvent;
use App\Features\Wallet;
use App\Models\BillingRate;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Traits\CacheDocumentTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class ShipmentBillingCacheService
{
    use CacheDocumentTrait;

    public function cacheShipments(bool $fromListener = false, Shipment ...$shipments): ShipmentCacheDocument|array|null
    {
        Log::channel('billing')->debug('Begins storing shipment documents',);
        if ($fromListener) {
            $results = $this->shipmentsForSingleOrder(...$shipments);
        } else {
            $results = $this->shipmentsFromMultipleOrders(...$shipments);
        }

        Log::channel('billing')->debug('End storing shipment documents');

        return $results;
    }

    public function shipmentsForSingleOrder(Shipment ...$shipments): ?ShipmentCacheDocument
    {

        try {
            $order = $this->getShipmentOrder(true, ...$shipments); // Exception if shipments don't belong to same array.

            $shipmentsDocument = ShipmentCacheDocument::where('order.id', $order->id)->first();
            if(is_null($shipmentsDocument)){

                $ids = array_map(fn($shipment) => $shipment->id, $shipments);
                Log::channel('billing')->debug(sprintf("Start storing shipment %s documents for a single Order: %s", implode(', ', $ids), $order->id));
                $this->validatesCustomerPermissions($order->customer);
                $this->validateShippingMethod(true, ...$shipments);
                $shipmentsDocument = ShipmentCacheDocument::makeFromModels($order, ...$shipments);
            }
        } catch (Throwable $e) {
            Log::warning($e->getMessage());
            return null;
        }
        $shipmentsDocument->save();

        return $shipmentsDocument;
    }

    public function shipmentsFromMultipleOrders(Shipment ...$shipments): array
    {
        $shipmentsOrderBy = array_reduce($shipments, function ($carry, $shipment) {
            $carry[$shipment->order_id][] = $shipment;
            return $carry;
        }, []);

        $shipmentsDocuments = [];
        foreach ($shipmentsOrderBy as $orderId => $shipments) {

            try {
                $order = Order::find($orderId); // Exception if shipments don't belong to same array.

                $shipmentsDocument = ShipmentCacheDocument::where('order.id', $order->id)->first();
                if(is_null($shipmentsDocument)){
                    Log::channel('billing')->debug("Start storing shipments for Orders :" . $order->id);
                    $this->validatesCustomerPermissions($order->customer);
                    foreach ($shipments as $shipment) {
                        $this->validateShippingMethod(false, $shipment);
                    }
                    $shipmentsDocument = ShipmentCacheDocument::makeFromModels($order, ...$shipments);
                    $shipmentsDocument->save();
                }
            } catch (Throwable $e) {
                report($e);
                continue;
            }
            $shipmentsDocuments[] = $shipmentsDocument;

        }
        return $shipmentsDocuments;
    }

    public function updateShipmentCalculatedBillingRate(
        ShipmentCacheDocument $shipmentCacheDocument,
        array $calculatedBillingRates
    ): bool
    {
        if (!$this->validateBillingRate($calculatedBillingRates)) {
            return false;
        }

        return $this->updateCalculatedBillingRate($shipmentCacheDocument, $calculatedBillingRates);
    }

    public function updateShipmentCalculatedBillingRateByBillingRate(
        ShipmentCacheDocument $shipmentCacheDocument,
        array $billingRates
    ): bool
    {
        $shipmentCacheDocument = $this->updateByBillingRates($shipmentCacheDocument, $billingRates);
        return $shipmentCacheDocument->save();
    }

    /**
     * @throws LogicException
     */
    private function getShipmentOrder(bool $sameOrder = false, Shipment ...$shipments): ?Order
    {
        $order = null;
        $error = false;
        $shipmentOrderIdError = null;
        collect($shipments)->each(function ($shipment) use (&$order, &$error, &$shipmentOrderIdError, &$sameOrder) {
            if (empty($order)) {
                $order = $shipment->order;
                return true;
            }

            $error = $order->id != $shipment->order->id;
            $shipmentOrderIdError = $shipment->order->id;
            return !$error;
        });

        if ($sameOrder && $error) {
            throw new LogicException(sprintf("Shipment %s with not matching orders", $shipmentOrderIdError));
        }

        return $order;
    }

    private function validatesCustomerPermissions(Customer $customer): void
    {
        if (!$customer->is3pl() && !$customer->is3plChild()) {
            throw new LogicException('Shipments documents not store. Customer is not 3pl or 3pl child');
        }
        $customer3pl = $customer->parent;

        if (!$customer3pl->hasFeature(Wallet::class)) {
            throw new LogicException('Shipments documents not store. Wallet feature disable');
        }
    }

    //TODO: Not sure if need it. Can be reverted.
    private function validateShippingMethod(bool $sameOrder = false, Shipment ...$shipments): void
    {
        $shippingMethod = null;
        $error = false;
        collect($shipments)->each(function ($shipment) use (&$shippingMethod, &$error, &$sameOrder) {
            if ($sameOrder && !empty($shippingMethod) && $shippingMethod->id != $shipment->shippingMethod->id) {
                $error = true;
                return false;
            }

            $shippingMethod = $shipment->shippingMethod;
            return true;
        });

        if ($error && $sameOrder) {
            throw new LogicException('Shipping method not matching shipments');
        }
    }

    private function updateCalculatedBillingRate(ShipmentCacheDocument $shipmentCacheDocument, array $calculatedBillingRate): bool
    {
        $shipmentCacheDocument = $this->updateBillingRates($shipmentCacheDocument, $calculatedBillingRate);
        return $shipmentCacheDocument->save();
    }

    /**
     * @param array $calculatedBillingRates
     * @return bool
     */
    private function validateBillingRate(array $calculatedBillingRates): bool
    {
        $result = true;
        if (empty($calculatedBillingRates)) {
            return false;
        }

        foreach ($calculatedBillingRates as $billingRate) {
            if (
                !array_key_exists('billing_rate_id', $billingRate) ||
                !array_key_exists('calculated_at', $billingRate) ||
                !array_key_exists('charges', $billingRate)
            ) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}
