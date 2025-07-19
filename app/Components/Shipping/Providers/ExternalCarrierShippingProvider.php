<?php

namespace App\Components\Shipping\Providers;

use App\Components\ReturnComponent;
use App\Components\ShippingComponent;
use App\Exceptions\ShippingException;
use App\Http\Requests\Order\StoreReturnRequest as StoreOrderReturnRequest;
use App\Http\Requests\Packing\PackageItemRequest;
use App\Http\Requests\Packing\StoreRequest;
use App\Http\Requests\Shipment\ShipItemRequest;
use App\Interfaces\BaseShippingProvider;
use App\Interfaces\ShippingProviderCredential;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerSetting;
use App\Models\ExternalCarrierCredential;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Return_;
use App\Models\Shipment;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use Carbon\Carbon;
use GuzzleHttp\{Client, Exception\RequestException};
use Illuminate\{Database\Eloquent\Collection, Support\Arr, Support\Facades\Log};

class ExternalCarrierShippingProvider implements BaseShippingProvider
{
    /**
     * @param ExternalCarrierCredential|null $credential
     * @return void
     * @throws ShippingException
     */
    public function getCarriers(ShippingProviderCredential $credential = null)
    {
        $carrierService = array_search(get_class($this), ShippingComponent::SHIPPING_CARRIERS);
        $credentials = new Collection();

        if (!is_null($credential)) {
            $credentials->add($credential);
        } else {
            $credentials = ExternalCarrierCredential::all();
        }
        foreach ($credentials as $credential) {
            $customer = $credential->customer;

            if (!$credential->get_carriers_url) {
                return;
            }

            try {
                $carriersResponse = $this->send($credential, 'GET', $credential->get_carriers_url);

                if (is_array($carriersResponse)) {
                    $storedCarrierIds = [];
                    $storedMethodIds = [];

                    foreach ($carriersResponse as $carrier) {
                        $externalCarrierId = (int) Arr::get($carrier, 'id');
                        $query = ShippingCarrier::withTrashed()
                            ->where('customer_id', $customer->id)
                            ->where('carrier_account', Arr::get($carrier, 'carrier_account'))
                            ->where('carrier_service', $carrierService);
                            if ($externalCarrierId) {
                                $query->whereJsonContains('settings', ['external_carrier_id' => $externalCarrierId]);
                            }
                            $shippingCarrier = $query->first();

                        if (!$shippingCarrier) {
                            $shippingCarrier = ShippingCarrier::create([
                                'customer_id' => $customer->id,
                                'carrier_service' => $carrierService,
                                'carrier_account' => Arr::get($carrier, 'carrier_account'),
                                'settings' => [
                                    'external_carrier_id' => $externalCarrierId
                                ]
                            ]);

                            $shippingCarrier->credential()->associate($credential);
                        }

                        $shippingCarrier->name = Arr::get($carrier, 'name');
                        $shippingCarrier->settings = [
                            'external_carrier_id' => $externalCarrierId
                        ];
                        $shippingCarrier->save();
                        $shippingCarrier->restore();

                        $storedCarrierIds[] = $shippingCarrier->id;

                        if (Arr::has($carrier, 'methods')) {
                            foreach ($carrier['methods'] as $method) {
                                $shippingMethod = ShippingMethod::withTrashed()->firstOrCreate([
                                    'shipping_carrier_id' => $shippingCarrier->id,
                                    'name' => Arr::get($method, 'name')
                                ]);
                                if (Arr::has($method, 'id')) {
                                    $settings = [
                                        'external_method_id' => Arr::get($method, 'id')
                                    ];
                                    $shippingMethod->settings = $settings;
                                }
                                $shippingMethod->save();
                                $shippingMethod->restore();
                                $storedMethodIds[] = $shippingMethod->id;
                            }
                        }

                        $shippingCarrier->shippingMethods()
                            ->withTrashed()
                            ->whereNotIn('id', $storedMethodIds)
                            ->delete();
                    }

                    $shippingCarriersToDelete = $credential->shippingCarriers()
                        ->withTrashed()
                        ->whereNotIn('id', $storedCarrierIds)
                        ->get();

                    foreach ($shippingCarriersToDelete as $shippingCarrierToDelete) {
                        $shippingCarrierToDelete->shippingMethods()->withTrashed()->delete();
                        $shippingCarrierToDelete->delete();
                    }
                }
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }

    /**
     * @param Order $order
     * @param StoreRequest $storeRequest
     * @return array
     * @throws ShippingException
     * @throws \JsonException
     */
    public function ship(Order $order, StoreRequest $storeRequest): array
    {
        $input = $storeRequest->all();

        if (empty($input['shipping_method_id'])) {
            $shippingMethod = $order->shippingMethod;
        } else {
            $shippingMethod = ShippingMethod::find($input['shipping_method_id']);
        }

        if (empty($shippingMethod->shippingCarrier->credential->create_shipment_label_url)) {
            return [];
        }

        $orderItemsToShip = [];
        $packageItemRequests = [];

        // TODO: rewrite, make it more simple
        foreach ($input['order_items'] as $record) {
            $shipItemRequest = ShipItemRequest::make($record);
            $orderItem = OrderItem::find($record['order_item_id']);
            $orderItemsToShip[] = ['orderItem' => $orderItem, 'shipRequest' => $shipItemRequest];
        }

        $packingState = json_decode($input['packing_state'], true);

        // TODO: rewrite, make it more simple
        foreach ($packingState as $packingStateItem) {
            $packageItemRequest = PackageItemRequest::make($packingStateItem);
            $packageItemRequests[] = $packageItemRequest;
        }

        $shipmentRequestBody = $this->createShipmentRequestBody($order, $input, $shippingMethod);

        $response = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            $shippingMethod->shippingCarrier->credential->create_shipment_label_url,
            $shipmentRequestBody
        );

        if ($response) {
            $shipment = app('shipping')->createShipment($order, $shippingMethod, $input, $this->getShipmentTotalCost($response), Arr::get($response, 'id') ?? null);

            if (Arr::get($response, 'id')) {
                Log::info('Shipment ID [external carrier]: ' . Arr::get($response, 'id'));
            }

            app('shipment')->createContactInformation($order->shippingContactInformation->toArray(), $shipment);

            foreach ($orderItemsToShip as $orderItemToShip) {
                app('shipment')->shipItem($orderItemToShip['shipRequest'], $orderItemToShip['orderItem'], $shipment);
            }

            foreach ($packageItemRequests as $packageItemRequest) {
                app('shipping')->createPackage($order, $packageItemRequest, $shipment);
            }

            $this->storeShipmentLabelAndTracking($shipment, $response);

            return [$shipment];
        }

        return [];
    }

    /**
     * @param Shipment $shipment
     * @return array
     * @throws ShippingException
     * @throws \JsonException
     */
    public function void(Shipment $shipment): array
    {
        if (empty($shipment->shippingMethod->shippingCarrier->credential->void_label_url)) {
            return ['success' => false, 'message' => __('Carrier void label url not found!')];
        }

        $requestBody = $this->createVoidRequestBody($shipment);

        $response = $this->send(
            $shipment->shippingMethod->shippingCarrier->credential,
            'POST',
            $shipment->shippingMethod->shippingCarrier->credential->void_label_url,
            $requestBody
        );

        if (!empty($response)) {
            $shipment->voided_at = Carbon::now();

            $shipment->saveQuietly();

            return ['success' => true, 'message' => __('Shipment successfully voided.')];
        }

        return ['success' => false, 'message' => __('Something went wrong!')];
    }

    public function return(Order $order, StoreOrderReturnRequest $storeRequest): ?Return_
    {
        $input = $storeRequest->all();

        $input['number'] = Return_::getUniqueIdentifier(ReturnComponent::NUMBER_PREFIX, $input['warehouse_id']);

        $shippingRateId = $input['shipping_method_id'];
        $shippingMethod = ShippingMethod::find($shippingRateId);

        if (empty($shippingMethod->shippingCarrier->credential->create_return_label_url)) {
            return null;
        }

        $requestBody = $this->createReturnRequestBody($order, $storeRequest, $shippingMethod);

        $response = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            $shippingMethod->shippingCarrier->credential->create_return_label_url,
            $requestBody
        );

        if ($response) {
            $return = app('return')->createReturn($order, $input);

            $this->storeReturnLabelAndTracking($return, $response);

            return $return;

        }

        return null;
    }

    public function manifest(ShippingCarrier $shippingCarrier)
    {
        // TODO: Implement manifest() method.
    }

    private function send(ExternalCarrierCredential $externalCarrierCredential, $method, $url, $data = null, $returnException = true)
    {
        Log::info('[External carrier] send', [
            'external_carrier_credential_id' => $externalCarrierCredential->id,
            'method' => $method,
            'url' => $url,
            'data' => $data
        ]);

        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/vnd.api+json',
            ]
        ]);

        try {
            Log::debug($url);
            $response = $client->request($method, $url, $method == 'GET' ? [] : ['body' => json_encode($data)]);
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            Log::info('[External carrier] response', [$body]);

            return $body;
        } catch (RequestException $exception) {
            $logLevel = 'error';

            if (\Str::startsWith($exception->getResponse()->getStatusCode(), 4)) {
                $logLevel = 'info';
            }

            Log::log($logLevel, '[External carrier] status', [$exception->getResponse()->getStatusCode()]);
            Log::log($logLevel, '[External carrier] exception thrown', [$exception->getResponse()->getBody()]);
            Log::log($logLevel, '[External carrier] exception message thrown', [$exception->getMessage()]);

            if ($returnException) {
                throw new ShippingException($exception->getResponse()->getBody());
            }
        }

        return null;
    }

    public function createVoidRequestBody(Shipment $shipment): array
    {
        $request = [];
        $request['id'] = $shipment->external_shipment_id;
        $request['shipping_carrier_id'] = $shipment->shippingMethod->shippingCarrier->settings['external_carrier_id'] ?? '';
        $request['tracking_numbers'] = [];
        foreach ($shipment->shipmentTrackings as $key => $tracking) {
            $request['tracking_numbers'][$key]['number'] = $tracking->tracking_number;
        }

        return $request;
    }

    /**
     * @param Order $order
     * @param $input
     * @param ShippingMethod $shippingMethod
     * @return array
     */
    public function createShipmentRequestBody(Order $order, $input, ShippingMethod $shippingMethod): array
    {
        $dimensionUnit = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT, Customer::DIMENSION_UNIT_DEFAULT);
        $weightUnit = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT, Customer::WEIGHT_UNIT_DEFAULT);
        $customerAddress = $order->customer->contactInformation;
        $customerWarehouseAddress = $order->customer->shipFromContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->shipFromContactInformation;
        }

        if ($customerWarehouseAddress) {
            $senderName = $customerWarehouseAddress->name;
        }

        $orderItemsToShip = [];
        $packageItemRequests = [];

        $request = [];
        $request['shipping_carrier_id'] = $shippingMethod->shippingCarrier->settings['external_carrier_id'] ?? null;
        $request['shipping_method'] = $shippingMethod->name;
        $request['order_number'] = str_replace(' ', '-', $order->customer->contactInformation->name . ' ' . $order->number);

        foreach ($input['order_items'] as $record) {
            $shipItemRequest = ShipItemRequest::make($record);
            $orderItem = OrderItem::find($record['order_item_id']);
            $orderItemsToShip[] = ['orderItem' => $orderItem, 'shipRequest' => $shipItemRequest];
        }

        $packingState = json_decode($input['packing_state'], true);

        foreach ($packingState as $packingStateItem) {
            $packageItemRequest = PackageItemRequest::make($packingStateItem);
            $packageItemRequests[] = $packageItemRequest;
        }

        if ($order->currency) {
            $currency = $order->currency->code;
        } else {
            $customerCurrency = Currency::find(customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_CURRENCY));

            if ($customerCurrency) {
                $currency = $customerCurrency->code;
            }
        }

        foreach ($packageItemRequests as $key => $packageToItem) {
            $packageToItemCount = $key + 1;
            $orderLines = [];
            $itemsPackedInThisPackage = [];
            $tmpCountArr = [];

            foreach ($packageToItem->items as $itemsIdLocArr ){
                $tmpCountArr[] = $itemsIdLocArr['orderItem'];
            }

            foreach( $packageToItem->items as $packItem ){
                $packItemId = $packItem['orderItem'];

                if (empty($customerWarehouseAddress)) {
                    /** @var Location $senderLocation */
                    $senderLocation = Location::find($packItem['location']);
                    $customerWarehouseAddress = $senderLocation->warehouse->contactInformation;
                    $senderName = $customerAddress->name;
                }

                foreach ($orderItemsToShip as $orderItemToShip) {
                    if ($orderItemToShip['orderItem']->id == $packItemId && !in_array($packItemId, $itemsPackedInThisPackage)) {
                        $itemsPackedInThisPackage[] = $packItemId;

                        $description = $orderItemToShip['orderItem']->product->customs_description;

                        if (empty($description)) {
                            $description = $orderItemToShip['orderItem']->name;
                        }

                        $item['sku'] = $orderItemToShip['orderItem']->sku;
                        $item['description'] = mb_substr($description, 0, 50);

                        $tmp = array_count_values($tmpCountArr);
                        $item['quantity'] = $tmp[$packItemId];
                        $item['hs_code'] = $orderItemToShip['orderItem']->product->hs_code;

                        $item['country_of_origin'] = $orderItemToShip['orderItem']->product->country ? $orderItemToShip['orderItem']->product->country->iso_3166_2 : null;

                        $item['unit_price'] = $orderItemToShip['orderItem']->priceForCustoms();

                        $item['currency'] = $currency ?? null;

                        $item['weight'] = $orderItemToShip['orderItem']->product->weight * $tmp[$packItemId];
                        $item['weight_unit'] = $weightUnit;

                        $orderLines[] = $item;
                        break;
                    }
                }
            }

            $request['packages'][] = [
                'number' => $packageToItemCount,
                'weight' => $packageToItem->weight,
                'weight_unit' => $weightUnit,
                'height' => $packageToItem->height,
                'width' => $packageToItem->width,
                'length' => $packageToItem->_length,
                'unit' => $dimensionUnit,
                'order_lines' => $orderLines,
            ];
        }

        $contactInformationData = $order->shippingContactInformation->toArray();

        // Sender (warehouse)
        $senderAddress['name'] = $senderName;
        $senderAddress['company_name'] = $customerAddress->company_name;
        $senderAddress['company_number'] = $customerAddress->company_number;
        $senderAddress['address_1'] = $customerWarehouseAddress->address ?? $customerAddress->address;
        $senderAddress['address_2'] = $customerWarehouseAddress->address2 ?? $customerAddress->address2;
        $senderAddress['zip'] = $customerWarehouseAddress->zip ?? $customerAddress->zip;
        $senderAddress['city'] = $customerWarehouseAddress->city ?? $customerAddress->city;
        $senderAddress['state'] = $customerWarehouseAddress->state ?? $customerAddress->state;
        $senderAddress['country_code'] = $customerWarehouseAddress->country->iso_3166_2 ?? $customerAddress->country->iso_3166_2;
        $senderAddress['email'] = $customerWarehouseAddress->email ?? $customerAddress->email;
        $senderAddress['phone'] = $customerWarehouseAddress->phone ?? $customerAddress->phone;
        $request['sender_address'] = $senderAddress;

        // Receiver (customer)
        $deliveryAddress['name'] = $contactInformationData['name'];
        $deliveryAddress['company_name'] = $contactInformationData['company_name'] ?? null;
        $deliveryAddress['company_number'] = $contactInformationData['company_number'] ?? null;
        $deliveryAddress['address_1'] = $contactInformationData['address'];
        $deliveryAddress['address_2'] = $contactInformationData['address2'];
        $deliveryAddress['zip'] = $contactInformationData['zip'];
        $deliveryAddress['city'] = $contactInformationData['city'];
        $deliveryAddress['state'] = $contactInformationData['state'];
        $deliveryAddress['country_code'] = $order->shippingContactInformation->country->iso_3166_2 ?? null;
        $deliveryAddress['email'] = $contactInformationData['email'];
        $deliveryAddress['phone'] = $contactInformationData['phone'];
        $request['delivery_address'] = $deliveryAddress;

        return $request;
    }


    /**
     * @param Order $order
     * @param $storeRequest
     * @param ShippingMethod $shippingMethod
     * @return array
     */
    public function createReturnRequestBody(Order $order, $storeRequest, ShippingMethod $shippingMethod): array
    {
        $input = $storeRequest->all();

        $customerWarehouseAddress = $order->customer->returnToContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->returnToContactInformation;

            if (empty($customerWarehouseAddress)) {
                $warehouse = $order->warehouse;

                if (!$warehouse) {
                    $warehouse = $order->customer->parent_id ? $order->customer->parent->warehouses->first() : $order->customer->warehouses->first();
                }

                $customerWarehouseAddress = $warehouse->contactInformation;
            }
        }

        $dimensionUnit = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT, Customer::DIMENSION_UNIT_DEFAULT);
        $weightUnit = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT, Customer::WEIGHT_UNIT_DEFAULT);
        $defaultBox = $order->getDefaultShippingBox();

        $orderItemsArr = [];
        $orderItemsToShip = [];
        $totalWeight = 0;

        $request = [];
        $request['shipping_carrier_id'] = $shippingMethod->shippingCarrier->settings['external_carrier_id'] ?? null;
        $request['shipping_method'] = $shippingMethod->name;
        $request['order_number'] = str_replace(' ', '-', $order->number);

        foreach ($input['order_items'] as $record)
        {
            $shipItemRequest = ShipItemRequest::make($record);
            $orderItem = OrderItem::find($record['order_item_id']);
            $orderItemsToShip[] = ['orderItem' => $orderItem, 'shipRequest' => $shipItemRequest];

            $totalWeight += $orderItem->weight;
            $orderItemsArr[] = [
                'orderItem' => $record['order_item_id'],
                'location' => $record['location_id'],
                'tote' => $record['tote_id'],
//                'serialNumber' => '',
//                'packedParentKey' => ''
            ];
        }

        $packingStateItem = [
            'items' => $orderItemsArr,
            'weight' => $totalWeight,
            'box' => $defaultBox->id,
            '_length' => $defaultBox->length,
            'width' => $defaultBox->width,
            'height' => $defaultBox->height,
        ];

        $packageItemRequest = PackageItemRequest::make($packingStateItem);
        $itemsPackedInThisPackage = [];
        $tmpCountArr = [];
        $orderLines = [];
        $currency = null;

        if ($order->currency) {
            $currency = $order->currency->code;
        } else {
            $customerCurrency = Currency::find(customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_CURRENCY));

            if ($customerCurrency) {
                $currency = $customerCurrency->code;
            }
        }

        foreach ($packageItemRequest->items as $itemsIdLocArr ){
            $tmpCountArr[] = $itemsIdLocArr['orderItem'];
        }

        foreach($packageItemRequest->items as $packItem){
            $packItemId = $packItem['orderItem'];

            if (empty($customerWarehouseAddress)) {
                /** @var Location $senderLocation */
                $senderLocation = Location::find($packItem['location']);
                $customerWarehouseAddress = $senderLocation->warehouse->contactInformation;
            }

            foreach ($orderItemsToShip as $orderItemToShip) {
                if ($orderItemToShip['orderItem']->id == $packItemId && !in_array($packItemId, $itemsPackedInThisPackage)) {
                    $itemsPackedInThisPackage[] = $packItemId;

                    $description = $orderItemToShip['orderItem']->product->customs_description;

                    if (empty($description)) {
                        $description = $orderItemToShip['orderItem']->name;
                    }

                    $item['sku'] = $orderItemToShip['orderItem']->sku;
                    $item['description'] = mb_substr($description, 0, 50);

                    $tmp = array_count_values($tmpCountArr);
                    $item['quantity'] = $tmp[$packItemId];
                    $item['hs_code'] = $orderItemToShip['orderItem']->product->hs_code;

                    $item['country_of_origin'] = null;
                    if ($orderItemToShip['orderItem']->product->country) {
                        $item['country_of_origin'] = $orderItemToShip['orderItem']->product->country->iso_3166_2;
                    }

                    $item['unit_price'] = $orderItemToShip['orderItem']->priceForCustoms();

                    $item['currency'] = $currency ?? null;

                    $item['weight'] = $orderItemToShip['orderItem']->product->weight * $tmp[$packItemId];
                    $item['weight_unit'] = $weightUnit;

                    $orderLines[] = $item;
                    break;
                }
            }
        }

        $request['packages'][] = [
            'number' => 1,
            'weight' => $packageItemRequest->weight,
            'weight_unit' => $weightUnit,
            'height' => $packageItemRequest->height,
            'width' => $packageItemRequest->width,
            'length' => $packageItemRequest->_length,
            'unit' => $dimensionUnit,
            'order_lines' => $orderLines,
        ];

        $customerAddress = $order->customer->contactInformation;
        $contactInformationData = $order->shippingContactInformation->toArray();

        // Sender (customer)
        $senderAddress['company_name'] = $contactInformationData['company_name'] ?? null;
        $senderAddress['company_number'] = $contactInformationData['company_number'] ?? null;
        $senderAddress['address_1'] = $contactInformationData['address'];
        $senderAddress['address_2'] = $contactInformationData['address2'];
        $senderAddress['zip'] = $contactInformationData['zip'];
        $senderAddress['city'] = $contactInformationData['city'];
        $senderAddress['state'] = $contactInformationData['state'];
        $senderAddress['country_code'] = $order->shippingContactInformation->country->iso_3166_2 ?? null;
        $senderAddress['email'] = $contactInformationData['email'];
        $senderAddress['phone'] = $contactInformationData['phone'];
        $request['sender_address'] = $senderAddress;

        // Receiver (warehouse)
        $deliveryAddress['company_name'] = $customerAddress->company_name;
        $deliveryAddress['company_number'] = $customerAddress->company_number;
        $deliveryAddress['address_1'] = $customerWarehouseAddress->address ?? $customerAddress->address;
        $deliveryAddress['address_2'] = $customerWarehouseAddress->address2 ?? $customerAddress->address2;
        $deliveryAddress['zip'] = $customerWarehouseAddress->zip ?? $customerAddress->zip;
        $deliveryAddress['city'] = $customerWarehouseAddress->city ?? $customerAddress->city;
        $deliveryAddress['state'] = $customerWarehouseAddress->state ?? $customerAddress->state;
        $deliveryAddress['country_code'] = $customerWarehouseAddress->country->iso_3166_2 ?? $customerAddress->country->iso_3166_2;
        $deliveryAddress['email'] = $customerWarehouseAddress->email ?? $customerAddress->email;
        $deliveryAddress['phone'] = $customerWarehouseAddress->phone ?? $customerAddress->phone;
        $request['delivery_address'] = $deliveryAddress;

        return $request;
    }

    /**
     * @param Return_ $return
     * @param $response
     * @return void
     */
    private function storeReturnLabelAndTracking(Return_ $return, $response): void
    {
        foreach (Arr::get($response, 'labels', []) as $label) {
            app('return')->storeReturnLabel(
                $return,
                null,
                null,
                Arr::get($label, 'label_url'),
                'pdf'
            );

            if (Arr::has($label, 'tracking_links.number')) {
                app('return')->storeReturnTracking(
                    $return,
                    Arr::get($label, 'tracking_links.number'),
                    Arr::get($label, 'tracking_links.link'),
                );
            }
        }
    }

    /**
     * @param Shipment $shipment
     * @param array $carrierResponse
     * @return void
     */
    private function storeShipmentLabelAndTracking(Shipment $shipment, array $carrierResponse): void
    {
        foreach (Arr::get($carrierResponse, 'labels', []) as $label) {
            app('shipping')->storeShipmentLabel(
                $shipment,
                null,
                null,
                Arr::get($label, 'label_url')
            );

            if (Arr::has($label, 'tracking_links.number')) {
                app('shipping')->storeShipmentTracking(
                    $shipment,
                    Arr::get($label, 'tracking_links.number'),
                    Arr::get($label, 'tracking_links.link')
                );
            } elseif (Arr::has($label, 'tracking_links.0.number')) {
                foreach (Arr::get($label, 'tracking_links', []) as $trackingLink) {
                    app('shipping')->storeShipmentTracking(
                        $shipment,
                        Arr::get($trackingLink, 'number'),
                        Arr::get($trackingLink, 'link')
                    );
                }
            }
        }
    }

    /**
     * Used for calculating shipment costs for external carrier shipping providers
     * @param array $carrierResponse
     * @return int
     */
    private function getShipmentTotalCost(array $carrierResponse): int
    {
        $totalCost = Arr::get($carrierResponse, 'total_cost', 0);
        if ($totalCost == 0) {
            foreach (Arr::get($carrierResponse, 'labels', []) as $label) {
                $totalCost += Arr::get($label, 'cost', 0);
            }
        }

        return $totalCost;
    }
}
