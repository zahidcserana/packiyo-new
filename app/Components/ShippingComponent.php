<?php

namespace App\Components;

use App\Components\Shipping\Providers\ExternalCarrierShippingProvider;
use App\Components\Shipping\Providers\GenericShippingProvider;
use App\Components\Shipping\Providers\EasypostShippingProvider;
use App\Components\Shipping\Providers\WebshipperShippingProvider;
use App\Components\Shipping\Providers\TribirdShippingProvider;
use App\Events\OrderShippedEvent;
use App\Exceptions\ShippingException;
use App\Interfaces\BaseShippingProvider;
use App\Models\Location;
use App\Models\LocationProduct;
use App\Models\Lot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageOrderItem;
use App\Models\Shipment;
use App\Models\ShipmentLabel;
use App\Models\ShipmentTracking;
use App\Models\ShippingMethod;
use App\Models\ShippingMethodMapping;
use App\Models\TribirdCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShippingComponent extends BaseComponent
{
    public const SHIPPING_CARRIER_SERVICE_GENERIC = 'generic';
    public const SHIPPING_CARRIER_SERVICE_EASYPOST = 'easypost';
    public const SHIPPING_CARRIER_SERVICE_WEBSHIPPER = 'webshipper';
    public const SHIPPING_CARRIER_SERVICE_TRIBIRD = 'tribird';

    public const SHIPPING_CARRIER_SERVICE_EXTERNAL = 'external';

    public const SHIPPING_CARRIERS = [
        ShippingComponent::SHIPPING_CARRIER_SERVICE_GENERIC => GenericShippingProvider::class,
        ShippingComponent::SHIPPING_CARRIER_SERVICE_EASYPOST => EasypostShippingProvider::class,
        ShippingComponent::SHIPPING_CARRIER_SERVICE_WEBSHIPPER => WebshipperShippingProvider::class,
        ShippingComponent::SHIPPING_CARRIER_SERVICE_TRIBIRD => TribirdShippingProvider::class,
        ShippingComponent::SHIPPING_CARRIER_SERVICE_EXTERNAL => ExternalCarrierShippingProvider::class,
    ];

    public function getCarriers()
    {
        foreach (self::SHIPPING_CARRIERS as $shippingCarrierClass) {
            /** @var BaseShippingProvider $shippingCarrierProvider */
            $shippingCarrierProvider = new $shippingCarrierClass();

            $shippingCarrierProvider->getCarriers();
        }
    }

    /**
     * @param Order $order
     * @param Request $storeRequest
     * @return mixed
     */
    public function ship(Order $order, Request $storeRequest): mixed
    {
        $input = $storeRequest->all();
        $shipments = DB::transaction(function () use ($order, $input, $storeRequest) {
            if (array_key_exists($input['shipping_method_id'], ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS)) {
                $cheapestShippingRates = $this->getCheapestShippingRates($order, $input);

                $shippingMethodType = $input['shipping_method_id'];

                if (!empty($cheapestShippingRates[$shippingMethodType])) {
                    $shippingMethod = ShippingMethod::find($cheapestShippingRates[$shippingMethodType]['shipping_method_id']);
                } else {
                    if ($shippingMethodType === ShippingMethodMapping::CHEAPEST_SHIPPING) {
                        throw new HttpException(500, __('No shipping methods available'));
                    }

                    throw new HttpException(500, __('No shipping methods available for the expected delivery time'));
                }
            } else if (empty($input['shipping_method_id'])) {
                $shippingMethod = $order->shippingMethod;
            } else {
                $shippingMethod = ShippingMethod::find($input['shipping_method_id']);
            }

            return $this->getShippingProvider($shippingMethod)->ship($order, $storeRequest, $shippingMethod);
        });

        event(new OrderShippedEvent($order, ...$shipments));

        return $shipments;
    }

    public function getShippingProvider(?ShippingMethod $shippingMethod): BaseShippingProvider
    {
        $shippingProviderClass = Arr::get(
            self::SHIPPING_CARRIERS,
            $shippingMethod->shippingCarrier->carrier_service ?? self::SHIPPING_CARRIERS[self::SHIPPING_CARRIER_SERVICE_GENERIC]
        );

        if (!$shippingProviderClass) {
            $shippingProviderClass = self::SHIPPING_CARRIERS[self::SHIPPING_CARRIER_SERVICE_GENERIC];
        }

        return new $shippingProviderClass;
    }

    public function return(Order $order, $request)
    {
        $input = $request->all();

        return DB::transaction(function () use ($order, $request, $input) {
            $shippingMethod = ShippingMethod::find($input['shipping_method_id']);

            $shippingProviderClass = Arr::get(self::SHIPPING_CARRIERS, $shippingMethod->shippingCarrier->carrier_service ?? self::SHIPPING_CARRIERS[self::SHIPPING_CARRIER_SERVICE_GENERIC]);

            if (!$shippingProviderClass) {
                $shippingProviderClass = self::SHIPPING_CARRIERS[self::SHIPPING_CARRIER_SERVICE_GENERIC];
            }

            return (new $shippingProviderClass)->return($order, $request);
        });
    }

    public function void(Shipment $shipment): mixed
    {
        $shippingProviderClass = Arr::get(
            self::SHIPPING_CARRIERS,
            $shipment->shippingMethod->shippingCarrier->carrier_service ?? self::SHIPPING_CARRIERS[self::SHIPPING_CARRIER_SERVICE_GENERIC]
        );

        if (!$shippingProviderClass) {
            $shippingProviderClass = self::SHIPPING_CARRIERS[self::SHIPPING_CARRIER_SERVICE_GENERIC];
        }

        $shippingProvider = new $shippingProviderClass;

        try {
            $voidResponse = $shippingProvider->void($shipment);

            $message = __('Shipment was voided.');

            if ($shipment->shipmentTrackings->count() > 0) {
                $message = __('Label with tracking number(s) <em>:tracking_numbers</em> was voided.', ['tracking_numbers' => $shipment->trackingNumbers()]);
            }

            Shipment::auditCustomEvent($shipment, 'voided', $message);

            $success = true;
            $message = Arr::get($voidResponse, 'message', __('Success'));
        } catch (ShippingException $exception) {
            $success = false;
            $message = $exception->getMessage();
        }


        return [
            'success' => $success,
            'message' => $message
        ];
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     */
    public function getShippingRates(Order $order, array $input, array $params = []): array
    {
        $easypostRates = $this->getShippingProviderRates(self::SHIPPING_CARRIER_SERVICE_EASYPOST, $order, $input, $params);
        $tribirdRates = $this->getShippingProviderRates(self::SHIPPING_CARRIER_SERVICE_TRIBIRD, $order, $input, $params);
        $webshipperRates = $this->getShippingProviderRates(self::SHIPPING_CARRIER_SERVICE_WEBSHIPPER, $order, $input, $params);

        $rates = $this->mergeShippingProviderRates($easypostRates, $tribirdRates);
        $rates = $this->mergeShippingProviderRates($rates, $webshipperRates);

        return array_merge(['cheapest_rate' => $this->getCheapestRate($rates)], $rates);
    }

    /**
     * @param string $provider
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     */
    private function getShippingProviderRates(string $provider, Order $order, array $input, array $params = []): array
    {
        $params['carrier_service'] = $provider;
        $params['credentials'] = $this->getProviderCredentials($provider, $order)['credentials'] ?? [];

        $shippingProviderClass = self::SHIPPING_CARRIERS[$params['carrier_service']];

        return (new $shippingProviderClass)->getShippingRates($order, $input, $params);
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     */
    public function getCheapestShippingRates(Order $order, array $input, array $params = []): array
    {
        $easypostCheapestRates = $this->getProviderCheapestShippingRates(self::SHIPPING_CARRIER_SERVICE_EASYPOST, $order, $input, $params);
        $tribirdCheapestRates = $this->getProviderCheapestShippingRates(self::SHIPPING_CARRIER_SERVICE_TRIBIRD, $order, $input, $params);
        $webshipperCheapestRates = $this->getProviderCheapestShippingRates(self::SHIPPING_CARRIER_SERVICE_WEBSHIPPER, $order, $input, $params);

        $rates = $this->getComparedCheapestShippingRates($easypostCheapestRates, $tribirdCheapestRates);

        return $this->getComparedCheapestShippingRates($rates, $webshipperCheapestRates);
    }

    /**
     * @param string $provider
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     */
    public function getProviderCheapestShippingRates(string $provider, Order $order, array $input, array $params = []): array
    {
        $params['carrier_service'] = $provider;
        $params['credentials'] = $this->getProviderCredentials($provider, $order)['credentials'] ?? [];

        $shippingProviderClass = self::SHIPPING_CARRIERS[$params['carrier_service']];

        return (new $shippingProviderClass)->getCheapestShippingRates($order, $input, $params);
    }

    /**
     * @param array $rates1
     * @param array $rates2
     * @return array
     */
    public function getComparedCheapestShippingRates(array $rates1, array $rates2): array
    {
        $rates = [];

        foreach (ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS as $key => $cheapestShippingMethod) {
            $rates[$key] = [];
        }

        foreach ($rates as $key => $aRate) {
            if (empty($rates1[$key]) && empty($rates2[$key])) {
                $rates[$key] = [];
            } elseif (!empty($rates1[$key]) && empty($rates2[$key])) {
                $rates[$key] = $rates1[$key];
            } elseif (empty($rates1[$key]) && !empty($rates2[$key])) {
                $rates[$key] = $rates2[$key];
            } elseif (!empty($rates1[$key]) && !empty($rates2[$key]) && $rates1[$key]['rate'] <= $rates2[$key]['rate']) {
                $rates[$key] = $rates1[$key];
            } else {
                $rates[$key] = $rates2[$key];
            }
        }

        return $rates;
    }

    /**
     * @param array $rates1
     * @param array $rates2
     * @return array
     */
    private function mergeShippingProviderRates(array $rates1, array $rates2): array
    {
        foreach ($rates2 as $key => $rate2) {
            if (isset($rates1[$key])) {
                $rates1[$key] = array_merge($rates1[$key], $rate2);
            } else {
                $rates1[$key] = $rate2;
            }
        }

        return $rates1;
    }

    /**
     * @param array $rates
     * @return array
     */
    private function getCheapestRate(array $rates): array
    {
        $cheapestRate = [];

        foreach ($rates as $carrier => $rate) {
            foreach ($rate as $service) {
                if (isset($service['service']) && (empty($cheapestRate) || $cheapestRate['rate'] > $service['rate'])) {
                    $cheapestRate = [
                        'carrier' => $carrier,
                        'service' => $service['service'],
                        'rate_id' => Arr::get($service, 'rate_id'),
                        'rate' => Arr::get($service, 'rate'),
                        'currency' => $service['currency'],
                        'delivery_days' => $service['delivery_days'],
                        'shipping_method_id' => $service['shipping_method_id']
                    ];
                }
            }
        }

        return $cheapestRate;
    }

    /**
    * @param string $provider
    * @param Order $order
    * @return array
    */
    private function getProviderCredentials($provider, $order)
    {
        $params = [];

        switch ($provider) {
            case self::SHIPPING_CARRIER_SERVICE_EASYPOST:
                $params['credentials'] = $order->customer->easypostCredentials;

                if ($order->customer->parent_id) {
                    $params['credentials'] = $params['credentials']->merge($order->customer->parent->easypostCredentials);
                }

                break;
            case self::SHIPPING_CARRIER_SERVICE_WEBSHIPPER:
                $params['credentials'] = $order->customer->webshipperCredentials;

                if ($order->customer->parent_id) {
                    $params['credentials'] = $params['credentials']->merge($order->customer->parent->webshipperCredentials);
                }

                break;
            case self::SHIPPING_CARRIER_SERVICE_TRIBIRD:
                if ($order->customer->tribirdCredential) {
                    $params['credentials'] = TribirdCredential::where('customer_id', $order->customer->id)->get();
                }

                if ($order->customer->parent_id && $order->customer->parent->tribirdCredential) {
                    $parentCredentials = TribirdCredential::where('customer_id', $order->customer->parent->id)->get();

                    $params['credentials'] = $order->customer->tribirdCredential ? $params['credentials']->merge($parentCredentials) : $parentCredentials;
                }

                break;
        }

        return $params;
    }

    /**
     * Returns array of skus as key and array of locations as value for the items that don't have enough inventory
     * to fulfill the packing state.
     *
     * @param string $packingState
     * @return array
     */
    public function notFulfillablePackingStateLocations(string $packingState): array
    {
        $packages = json_decode($packingState, true);
        $orderItems = [];
        $notEnoughInventory = [];

        foreach ($packages as $package) {
            $packageItems = Arr::get($package, 'items', []);

            foreach ($packageItems as $item) {
                $orderItemId = (int) $item['orderItem'];
                $locationId = (int) $item['location'];

                if (array_key_exists($orderItemId, $orderItems)) {
                    if (array_key_exists($locationId, $orderItems[$orderItemId])) {
                        $orderItems[$orderItemId][$locationId]++;
                    }
                } else {
                    $orderItems[$orderItemId] = [
                        $locationId => 1
                    ];
                }
            }
        }

        foreach ($orderItems as $orderItemId => $item) {
            foreach ($item as $location => $quantity) {
                $orderItem = OrderItem::find($orderItemId);

                $hasEnoughInventory = LocationProduct::whereLocationId($location)
                    ->where('product_id', $orderItem->product_id)
                    ->where('quantity_on_hand', '>=', $quantity)
                    ->exists();

                if (!$hasEnoughInventory) {
                    if (!isset($notEnoughInventory[$orderItem->sku])) {
                        $notEnoughInventory[$orderItem->sku] = [];
                    }

                    $notEnoughInventory[$orderItem->sku][] = Location::find($location)->name ?? '';
                }
            }
        }

        return $notEnoughInventory;
    }

    /**
     * @param Order $order
     * @param ShippingMethod|null $shippingMethod
     * @param $input
     * @param int $shipmentCost
     * @param null $externalShipmentId
     * @return Shipment
     * @throws ShippingException
     */
    public function createShipment(Order $order, ?ShippingMethod $shippingMethod, $input, $shipmentCost = 0, $externalShipmentId = null): Shipment
    {
        $shipment = new Shipment();
        $shipment->user_id = auth()->user()->id ?? 1;
        $shipment->order_id = $order->id;
        $shipment->shipping_method_id = $shippingMethod->id ?? null;
        $shipment->processing_status = Shipment::PROCESSING_STATUS_SUCCESS;
        $shipment->external_shipment_id = $externalShipmentId;
        $shipment->cost = $shipmentCost;

        if (Arr::get($input, 'drop_point_id')) {
            $shipment->drop_point_id = Arr::get($input, 'drop_point_id');
        }

        if ($shipment->save()) {
            return $shipment;
        }

        throw new ShippingException(__('Something went wrong while creating a shipment. Please try again.'));
    }

    /**
     * @param Order $order
     * @param $packageItemRequest
     * @param $shipment
     * @return Package
     * @throws ShippingException
     */
    public function createPackage(Order $order, $packageItemRequest, $shipment): Package
    {
        /** @var Package $package */
        $package = Package::create([
            'order_id' => $order->id,
            'shipping_box_id' => $packageItemRequest->box,
            'weight' => $packageItemRequest->weight,
            'length' => $packageItemRequest->_length,
            'width' => $packageItemRequest->width,
            'height' => $packageItemRequest->height,
            'shipment_id' => $shipment->id,
        ]);

        if (!$package) {
            throw new ShippingException(__('Unable to create a package for the shipment'));
        }

        $packageOrderItems = [];

        foreach ($packageItemRequest->items as $packItem) {
            $orderItemId = $packItem['orderItem'];
            $packItemLocation = $packItem['location'];
            $packItemTote = $packItem['tote'];
            $serialNumber = Arr::get($packItem, 'serialNumber');

            $packageToItemKey = $orderItemId . '_' . $packItemLocation . '_' . $packItemTote . '_' . $serialNumber;

            $lot = Lot::select('lots.id')
                ->join('lot_items', 'lot_items.lot_id', '=', 'lots.id')
                ->join('products', 'lots.product_id', '=', 'products.id')
                ->join('order_items', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.id', '=', $orderItemId)
                ->where('lot_items.location_id', '=', $packItemLocation)
                ->first();

            $lotId = $lot->id ?? null;

            if (isset($packageOrderItems[$packageToItemKey])) {
                $packageOrderItems[$packageToItemKey]['quantity']++;
            } else {
                $packageOrderItems[$packageToItemKey] = [
                    'order_item_id' => $orderItemId,
                    'package_id' => $package->id,
                    'location_id' => $packItemLocation,
                    'tote_id' => !empty($packItemTote) ? $packItemTote : null,
                    'serial_number' => $serialNumber,
                    'quantity' => 1,
                    'lot_id' => $lotId
                ];
            }
        }

        foreach ($packageOrderItems as $packageOrderItem) {
            PackageOrderItem::create($packageOrderItem);
        }

        return $package;
    }

    /**
     * @param Shipment $shipment
     * @param null $labelContent
     * @param null $labelSize
     * @param null $labelUrl
     * @param string $labelType
     * @param string|null $requestedLabelFormat
     * @return ShipmentLabel
     * @throws ShippingException
     */
    public function storeShipmentLabel(Shipment $shipment, $labelContent = null, $labelSize = null, $labelUrl = null, string $labelType = ShipmentLabel::TYPE_SHIPPING, ?string $requestedLabelFormat = 'pdf'): ShipmentLabel
    {
        try {
            $shipmentLabel = ShipmentLabel::create([
                'shipment_id' => $shipment->id,
                'size' => $labelSize ?? '',
                'url' => $labelUrl,
                'document_type' => $requestedLabelFormat,
                'content' => $labelContent,
                'type' => $labelType
            ]);
        } catch (\Exception $exception) {
            throw new ShippingException(__('Unable to create a shipment label. Please try again'));
        }

        if (!$shipmentLabel) {
            throw new ShippingException(__('Unable to create a shipment label. Please try again'));
        }

        return $shipmentLabel;
    }

    /**
     * @param Shipment $shipment
     * @param $trackingNumber
     * @param null $trackingUrl
     * @param string $trackingType
     * @return ShipmentTracking
     * @throws ShippingException
     */
    public function storeShipmentTracking(Shipment $shipment, $trackingNumber, $trackingUrl = null, string $trackingType = ShipmentTracking::TYPE_SHIPPING): ShipmentTracking
    {
        try {
            $shipmentTracking = ShipmentTracking::create([
                'shipment_id' => $shipment->id,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $trackingUrl,
                'type' => $trackingType
            ]);
        } catch (\Exception $exception) {
            throw new ShippingException(__('Unable to create a shipment tracking. Please try again.'));
        }

        if (!$shipmentTracking) {
            throw new ShippingException(__('Unable to create a shipment tracking. Please try again'));
        }

        return $shipmentTracking;
    }
}
