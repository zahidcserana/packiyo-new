<?php

namespace App\Components\Shipping\Providers;

use App\Components\{ReturnComponent, ShippingComponent};
use App\Exceptions\ShippingException;
use App\Http\Requests\{Packing\PackageItemRequest, Shipment\ShipItemRequest, ShippingMethod\DropPointRequest};
use App\Interfaces\{BaseShippingProvider, ShippingProviderCredential};
use App\Models\{Currency,
    Package,
    Customer,
    CustomerSetting,
    Location,
    Order,
    OrderItem,
    Return_,
    Shipment,
    ShipmentLabel,
    ShippingCarrier,
    ShippingMethod,
    PathaoCredential};
use GuzzleHttp\{Client, Exception\GuzzleException, Exception\RequestException};
use Illuminate\{Database\Eloquent\Collection,
    Http\JsonResponse,
    Support\Arr,
    Support\Facades\Log,
    Support\Str};
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Barryvdh\DomPDF\Facade\Pdf;

class PathaoShippingProvider implements BaseShippingProvider
{
    public const INTEGRATION_NAME = 'Pathao';
    public const ITEM_TYPE = 2;
    public const DELIVERY_TYPE = 48;

    public const DELIVERY_TYPES = [
                    ['id' => 48, 'name' => 'Normal Delivery'],
                    ['id' => 12, 'name' => 'On-Demand Delivery']
                ];

    public const ITEM_TYPES = [
                    // ['id' => 1, 'name' => 'Document' ],
                    ['id' => 2, 'name' => ''] //Parcel
                ];
    public const SHIPPING_METHOD_NAME = self::DELIVERY_TYPES[0]['name'];


    /**
     * @param ShippingProviderCredential|null $credential
     * @return void
     * @throws ShippingException
     * @throws \JsonException|GuzzleException
     */
    public function getCarriers(ShippingProviderCredential $credential = null)
    {
        $carrierService = array_search(get_class($this), ShippingComponent::SHIPPING_CARRIERS);
        $credentials = new Collection();

        if (!is_null($credential)) {
            $credentials->add($credential);
        } else {
            $credentials = PathaoCredential::all();
        }

        $pathaoShipperRateIds = [];

        foreach ($credentials as $credential) {
            if ($credential->store_id) {
                $shippingCarrier = $credential->shippingCarriers()
                    ->withTrashed()
                    ->where('customer_id', $credential->customer_id)
                    ->where('carrier_service', $carrierService)
                    ->first();

                if (!$shippingCarrier) {
                    $shippingCarrier = ShippingCarrier::create([
                        'customer_id' => $credential->customer_id,
                        'carrier_service' => $carrierService,
                        'integration' => self::INTEGRATION_NAME,
                        'settings' => []
                    ]);

                    $shippingCarrier->credential()->associate($credential);
                }

                $shippingCarrier->integration = self::INTEGRATION_NAME;
                $shippingCarrier->name = 'PC';

                $shippingCarrier->save();
                $shippingCarrier->restore();

                $shippingRates = [];

                foreach (self::DELIVERY_TYPES as $deliveryType) {
                    
                    foreach (self::ITEM_TYPES as $itemType) {
                        $shippingRates[] = [
                            'name' => $deliveryType['name'],
                            'shipping_method' => $deliveryType['id'] . '-' . $itemType['id'],
                            'delivery_type' => $deliveryType['id'],
                            'item_type' => $itemType['id']
                        ];
                    }
                }

                foreach ($shippingRates as $shippingRate) {
                    $pathaoShipperRateIds[] = $shippingRate['shipping_method'];

                    $shippingMethod = ShippingMethod::withTrashed()
                        ->where('shipping_carrier_id', $shippingCarrier->id)
                        ->whereJsonContains('settings', ['shipping_method' => $shippingRate['shipping_method']])
                        ->first();

                    if (!$shippingMethod) {
                        $shippingMethod = ShippingMethod::create([
                            'shipping_carrier_id' => $shippingCarrier->id
                        ]);
                    }

                    $shippingMethod->name = $shippingRate['name'];
                    $shippingMethod->settings = [
                        'shipping_method' => $shippingRate['shipping_method'],
                        'delivery_type' => $shippingRate['delivery_type'],
                        'item_type' => $shippingRate['item_type'],
                    ];

                    $shippingMethod->save();
                    $shippingMethod->restore();
                }

                $customerPathaoCarriers = ShippingCarrier::with('shippingMethods')
                    ->where('customer_id', $credential->customer_id)
                    ->where('carrier_service', $carrierService)
                    ->get();

                foreach ($customerPathaoCarriers as $pathaoCarrier) {
                    foreach ($pathaoCarrier->shippingMethods as $shippingMethod) {
                        if (!in_array($shippingMethod->settings['shipping_method'], $pathaoShipperRateIds)) {

                            $shippingMethod->shippingMethodMappings()->forceDelete();
                            $shippingMethod->returnShippingMethodMappings()->forceDelete();

                            $shippingMethod->delete();
                        }
                    }
                }
            }
        }
    }

    public function shippingResponse($response, $shipment)
    {
        if (!is_null($response)) {
            if (is_array($response) && isset($response['data'])) {
                $shipmentWebShipperId = $response['data']['id'];
                $shipment->update(
                    [
                        'processing_status' => Shipment::PROCESSING_STATUS_SUCCESS,
                        'pathao_shipment_id' => $shipmentWebShipperId
                    ]
                );
            } else {
                $response = json_decode($response, 1);
                if (isset($response['errors']) && count($response['errors']) > 0) {
                    $errorTitle = $response['errors'][0]['title'];
                    $errorDetail = $response['errors'][0]['detail'];
                } else {
                    $errorTitle = 'Unknown error';
                    $errorDetail = 'Unknown error';
                }
                $shipment->update(
                    [
                        'processing_status' => Shipment::PROCESSING_STATUS_FAILED
                    ]
                );
            }
        }
    }

    /**
     * @param Order $order
     * @param $storeRequest
     * @param ShippingMethod|null $shippingMethod
     * @return array
     * @throws GuzzleException
     * @throws ShippingException
     * @throws \JsonException
     */
    public function ship(Order $order, $storeRequest, ShippingMethod $shippingMethod = null): array
    {
        $input = $storeRequest->all();

        if (is_null($shippingMethod)) {
            $shippingRateId = $input['shipping_method_id'];

            if (empty($shippingRateId)) {
                $shippingMethod = $order->shippingMethod;
            } else {
                $shippingMethod = ShippingMethod::find($shippingRateId);
            }
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
            '/orders',
            $shipmentRequestBody
        );

        if ($response) {
            $shipment = app('shipping')->createShipment(
                            $order, 
                            $shippingMethod, 
                            $input, 
                            (float) Arr::get($response, 'data.delivery_fee'), 
                            $response['data']['consignment_id']
                        );

            app('shipment')->createContactInformation($order->shippingContactInformation->toArray(), $shipment);

            foreach ($orderItemsToShip as $orderItemToShip) {
                app('shipment')->shipItem($orderItemToShip['shipRequest'], $orderItemToShip['orderItem'], $shipment);
            }

            if ($order->shipments->count() === 1) {
                app('shipment')->shipVirtualProducts($order, $shipment);
            }

            foreach ($packageItemRequests as $packageItemRequest) {
                app('shipping')->createPackage($order, $packageItemRequest, $shipment);
            }
            
            $this->storeShipmentLabelAndTracking($shipment, $response);

            /**
             * TODO: Future implementations
            if (customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_AUTO_RETURN_LABEL) === '1') {
                $shippingMethod = app('shippingMethodMapping')->returnShippingMethod($order) ?? $shippingMethod;

                $this->createAutoReturnLabels($shipmentRequestBody, $shippingMethod, $shipment);
            }

            if ($shipment->order->custom_invoice_url) {
                $this->sendShipmentCustomInvoice($shippingMethod, $shipment);
            }
             */

            $order->shipping_method_id = $shippingMethod->id;
            $order->save();

            return [$shipment];
        }

        return [];
    }

    /**
     * @param Order $order
     * @param $storeRequest
     * @return Return_|null
     * @throws ShippingException
     * @throws \JsonException|GuzzleException
     */
    public function return(Order $order, $storeRequest): ?Return_
    {
        $input = $storeRequest->all();

        $input['number'] = Return_::getUniqueIdentifier(ReturnComponent::NUMBER_PREFIX, $input['warehouse_id']);

        $shippingRateId = $input['shipping_method_id'];
        $shippingMethod = ShippingMethod::find($shippingRateId);

        $requestBody = $this->createReturnRequestBody($order, $storeRequest, $shippingMethod);

        $response = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            '/shipments?include=labels',
            $requestBody
        );

        if ($response) {
            $return = app('return')->createReturn($order, $input);

            $this->storeReturnLabelAndTracking($return, $response);

            return $return;

        }

        return null;
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

        $orderItemsToShip = [];
        $packageItemRequests = [];
        $packages = [];

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

        $customerAddress = $order->customer->contactInformation;
        $customerWarehouseAddress = $order->customer->shipFromContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->shipFromContactInformation;
        }

        if ($customerWarehouseAddress) {
            $senderName = $customerWarehouseAddress->name;
        }

        foreach ($packageItemRequests as $packageToItem) {
            $customsLines = [];
            $dimensions['height'] = $packageToItem->height;
            $dimensions['width'] = $packageToItem->width;
            $dimensions['length'] = $packageToItem->_length;
            $dimensions['unit'] = $dimensionUnit;

            $itemsPackedInThisPackage = [];

            $tmpCountArr = [];

            foreach ($packageToItem->items as $itemsIdLocArr ){
                $tmpCountArr[] = $itemsIdLocArr['orderItem'];//.'-'.$itemsIdLocArr['location'];
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
                        $item['unit_price'] = $orderItemToShip['orderItem']->priceForCustoms();

                        if ($orderItemToShip['orderItem']->product->country) {
                            $item['country_of_origin'] = $orderItemToShip['orderItem']->product->country->iso_3166_2;
                        }

                        $item['tarif_number'] = $orderItemToShip['orderItem']->product->hs_code;
                        $item['weight'] = $orderItemToShip['orderItem']->product->weight * $tmp[$packItemId];
                        $item['weight_unit'] = $weightUnit;

                        $customsLines[] = $item;
                        break;
                    }
                }
            }

            $packages[] = [
                'customs_lines' => $customsLines,
                'dimensions' => $dimensions,
                'weight' => $packageToItem->weight,
                'weight_unit' => $weightUnit
            ];
        }

        $totalAmount = 0;
        $totalWeight = 0;
        $totalItem = 0;

        foreach ($packages as $package) {
            $totalWeight += $package['weight'];
            $totalItem += count($package['customs_lines']);
            foreach ($package['customs_lines'] as $orderLine) {
                $totalAmount += $orderLine['unit_price'] * $orderLine['quantity'];
            }
        }

        $contactInformationData = $order->shippingContactInformation->toArray();
        $deliveryAddress = $this->setAddressForRequest($contactInformationData, $order);

        return [
            "store_id" => $shippingMethod->shippingCarrier->credential->store_id,
            "merchant_order_id" => (string) $order->number,
            "recipient_name" => $deliveryAddress['att_contact'],
            "recipient_phone" => $deliveryAddress['phone'],
            "recipient_address" => $deliveryAddress['address_1'],
            // "recipient_city" => (int) $deliveryAddress['city'] ?? 1,   // required by Pathao
            // "recipient_zone" => (int) $deliveryAddress['zip'],        // must come from your UI
            // "recipient_area" => (int) $deliveryAddress['state'],        // must come from your UI
            "delivery_type" => (int) $shippingMethod->settings['delivery_type'], // from unified dropdown
            "item_type" => (int) $shippingMethod->settings['item_type'],         // from unified dropdown
            "special_instruction" => $order->packing_note,
            "item_quantity" => $totalItem,
            "item_weight" => $totalWeight,
            "item_description" => $order->slip_note ?? "Shipment for Order {$order->number}",
            "amount_to_collect" => $totalAmount, // or $input['amount_to_collect'] if COD only
        ];
    }

    public function createReturnRequestBody(Order $order, $storeRequest, ShippingMethod $shippingMethod)
    {
        $input = $storeRequest->all();

        $customerWarehouseAddress = $order->customer->returnToContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->returnToContactInformation;
        }

        if ($customerWarehouseAddress) {
            $senderName = $customerWarehouseAddress->name;
        }

        $dimensionUnit = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT, Customer::DIMENSION_UNIT_DEFAULT);
        $weightUnit = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT, Customer::WEIGHT_UNIT_DEFAULT);

        $defaultBox = $order->getDefaultShippingBox();

        $orderItemsArr = [];
        $customsLines = [];
        $orderItemsToShip = [];
        $totalWeight = 0;

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
                'serialNumber' => '',
                'packedParentKey' => ''
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

        $request['data']['type'] = 'shipments';
        $request['data']['attributes']['reference'] = $order->customer->contactInformation->name . ' ' . $order->number;

        $dimensions['height'] = $packageItemRequest->height;
        $dimensions['width'] = $packageItemRequest->width;
        $dimensions['length'] = $packageItemRequest->_length;
        $dimensions['unit'] = $dimensionUnit;

        $itemsPackedInThisPackage = [];

        $tmpCountArr = [];

        foreach( $packageItemRequest->items as $itemsIdLocArr ){
            $tmpCountArr[] = $itemsIdLocArr['orderItem'];//.'-'.$itemsIdLocArr['location'];
        }

        $currency = null;

        if ($order->currency) {
            $currency = $order->currency->code;
        } else {
            $customerCurrency = Currency::find(customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_CURRENCY));

            if ($customerCurrency) {
                $currency = $customerCurrency->code;
            }
        }

        if ($currency) {
            $request['data']['attributes']['currency'] = $currency;
        }

        foreach ($packageItemRequest->items as $packItem) {
            $packItemId = $packItem['orderItem'];

            if (empty($customerWarehouseAddress)) {
                /** @var Location $senderLocation */
                $senderLocation = Location::find($packItem['location']);
                $customerWarehouseAddress = $senderLocation->warehouse->contactInformation;

                $senderName = $order->customer->contactInformation->name;
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

                    $tmp = arrayßcount_values($tmpCountArr);
                    $item['quantity'] = $tmp[$packItemId];

                    $item['unit_price'] = $orderItemToShip['orderItem']->priceForCustoms();

                    if ($currency) {
                        $item['currency'] = $currency;
                    }

                    if ($orderItemToShip['orderItem']->product->country) {
                        $item['country_of_origin'] = $orderItemToShip['orderItem']->product->country->iso_3166_2;
                    }

                    $item['tarif_number'] = $orderItemToShip['orderItem']->product->hs_code;
                    $item['weight'] = $orderItemToShip['orderItem']->product->weight * $tmp[$packItemId];

                    $customsLines[] = $item;
                    break;
                }
            }
        }

        $request['data']['attributes']['packages'][] = [
            'customs_lines' => $customsLines,
            'dimensions' => $dimensions,
            'weight' => $packageItemRequest->weight,
            'weight_unit' => $weightUnit,
        ];

        $customerAddress = $order->customer->contactInformation;
        $contactInformationData = $order->shippingContactInformation->toArray();

        if (empty($customerWarehouseAddress)) {
            $warehouse = $order->warehouse;

            if (!$warehouse) {
                $warehouse = app('packing')->getSenderWarehouse($order, $packageItemRequest->all());
            }

            $customerWarehouseAddress = $warehouse->contactInformation;
            $senderName = $customerAddress->name;
        }

        // Receiver (warehouse)
        $deliveryAddress['att_contact'] = $senderName;
        $deliveryAddress['company_name'] = $customerAddress->company_name;
        $deliveryAddress['eori'] = $customerAddress->company_number;
        $deliveryAddress['address_1'] = $customerWarehouseAddress->address ?? $customerAddress->address;
        $deliveryAddress['address_2'] = $customerWarehouseAddress->address2 ?? $customerAddress->address2;
        $deliveryAddress['zip'] = $customerWarehouseAddress->zip ?? $customerAddress->zip;
        $deliveryAddress['city'] = $customerWarehouseAddress->city ?? $customerAddress->city;
        $deliveryAddress['state'] = $customerWarehouseAddress->state ?? $customerAddress->state;
        $deliveryAddress['country_code'] = $customerWarehouseAddress->country->iso_3166_2 ?? $customerAddress->country->iso_3166_2;
        $deliveryAddress['email'] = $customerWarehouseAddress->email ?? $customerAddress->email;
        $deliveryAddress['phone'] = $customerWarehouseAddress->phone ?? $customerAddress->phone;
        $deliveryAddress['address_type'] = 'recipient';
        $request['data']['attributes']['delivery_address'] = $deliveryAddress;
        // Sender (customer)
        $senderAddress['att_contact'] = $contactInformationData['name'];
        $senderAddress['company_name'] = $contactInformationData['company_name'] ?? null;
        $senderAddress['address_1'] = $contactInformationData['address'];
        $senderAddress['address_2'] = $contactInformationData['address2'];
        $senderAddress['zip'] = $contactInformationData['zip'];
        $senderAddress['city'] = $contactInformationData['city'];
        $senderAddress['state'] = $contactInformationData['state'];
        $senderAddress['country_code'] = $order->shippingContactInformation->country->iso_3166_2 ?? null;
        $senderAddress['email'] = $contactInformationData['email'];
        $senderAddress['phone'] = $contactInformationData['phone'];
        $request['data']['attributes']['sender_address'] = $senderAddress;

        if (!empty($input['drop_point_id'])) {
            $request['data']['attributes']['drop_point']['drop_point_id'] = $input['drop_point_id'];
        }

        $request['data']['relationships']['shipping_rate']['data']['id'] = $shippingMethod->settings['external_method_id'];
        $request['data']['relationships']['shipping_rate']['data']['type'] = 'shipping_rates';

        return $request;
    }

    /**
     * @param Shipment $shipment
     * @return void
     */
    private function storeShipmentLabelAndTracking(Shipment $shipment, $carrierResponse): void
    {
        $trackingNumber = $shipment->order->number;

        if (str_starts_with($trackingNumber, '#')) {
            $trackingNumber = str_replace('#', '', $trackingNumber);
        }

        app('shipping')->storeShipmentTracking(
            $shipment,
            trim($trackingNumber)
        );

        foreach ($shipment->packages as $package) {
            app('shipping')->storeShipmentLabel(
                $shipment,
                base64_encode($this->generateLabel($package)),
                null,
                null,
            );

            if (customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_AUTO_RETURN_LABEL) === '1') {
                app('shipping')->storeShipmentLabel(
                    $shipment,
                    base64_encode($this->generateLabel($package, ShipmentLabel::TYPE_RETURN)),
                    null,
                    null,
                    ShipmentLabel::TYPE_RETURN
                );
            }
        }
    }
    /**
     * @param Shipment $shipment
     * @param $carrierResponse
     * @param string $shipmentLabelType
     * @return void
     */
    private function storeShipmentLabelAndTracking_old(Shipment $shipment, $carrierResponse, string $shipmentLabelType = ShipmentLabel::TYPE_SHIPPING): void
    {
        foreach (Arr::get($carrierResponse, 'included', []) as $included) {
            if (Arr::get($included, 'type') === 'labels') {
                app('shipping')->storeShipmentLabel(
                    $shipment,
                    Arr::get($included, 'attributes.base64'),
                    Arr::get($included, 'attributes.label_size'),
                    null,
                    $shipmentLabelType
                );
            }
        }

        foreach (Arr::get($carrierResponse, 'data.attributes.tracking_links', []) as $trackingLink) {
            app('shipping')->storeShipmentTracking(
                $shipment,
                Arr::get($trackingLink, 'number'),
                Arr::get($trackingLink, 'url'),
                $shipmentLabelType
            );
        }
    }

    /**
     * @param Return_ $return
     * @param $carrierResponse
     * @return void
     */
    private function storeReturnLabelAndTracking(Return_ $return, $carrierResponse): void
    {
        foreach (Arr::get($carrierResponse, 'included', []) as $included) {
            if (Arr::get($included, 'type') === 'labels') {
                app('return')->storeReturnLabel(
                    $return,
                    Arr::get($included, 'attributes.base64'),
                    Arr::get($included, 'attributes.label_size'),
                    null,
                    'pdf'
                );

            }
        }

        foreach (Arr::get($carrierResponse, 'data.attributes.tracking_links', []) as $trackingLink) {
            app('return')->storeReturnTracking(
                $return,
                Arr::get($trackingLink, 'number'),
                Arr::get($trackingLink, 'url'),
            );
        }

    }

    /**
     * @throws GuzzleException
     * @throws ShippingException
     * @throws \JsonException
     */
    private function send(PathaoCredential $pathaoCredential, $method, $endpoint, $data = null, $returnException = true)
    {
        Log::info('[Pathao] send', [
            'pathao_credential_id' => $pathaoCredential->id,
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data
        ]);

        $credentials = $this->getApiCredentials($pathaoCredential);
        $url = $credentials['baseUrl'] . $endpoint;

        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . Arr::get($credentials, 'apiKey'),
            ]
        ]);

        try {
            Log::debug($url);
            $response = $client->request($method, $url, $method == 'GET' ? [] : ['body' => json_encode($data)]);
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            Log::info('[Pathao] response', [$body]);

            return $body;
        } catch (RequestException $exception) {
            $logLevel = 'error';

            if (Str::startsWith($exception->getResponse()->getStatusCode(), 4)) {
                $logLevel = 'info';
            }

            Log::log($logLevel, '[Pathao] exception thrown', [$exception->getResponse()->getBody()]);

            if ($returnException) {
                throw new ShippingException($exception->getResponse()->getBody());
            }
        }

        return null;
    }

    private function getApiCredentials(PathaoCredential $pathaoCredential)
    {
        $baseUrl = '';
        $apiKey = '';

        if ($pathaoCredential) {
            $baseUrl = rtrim($pathaoCredential->api_base_url, '/');
            // $apiKey = $pathaoCredential->api_key;

            $response = Http::post($baseUrl . '/issue-token', [
                'client_id' => $pathaoCredential->client_id,
                'client_secret' => $pathaoCredential->client_secret,
                'username' => $pathaoCredential->username,
                'password' => $pathaoCredential->password,
                'grant_type' => 'password',
            ]);

            $apiKey = $response->json()['access_token'] ?? null;
        }

        return compact('baseUrl', 'apiKey');
    }

    public function getDropPoints(DropPointRequest $request): JsonResponse
    {
        $input = $request->validated();

        $shippingMethod = ShippingMethod::find($input['shipping_method_id']);
        $order = Order::find($input['order_id']);

        $deliveryAddress['address_1'] = $input['address'];
        $deliveryAddress['zip'] = $input['zip'];
        $deliveryAddress['city'] = $input['city'];
        $deliveryAddress['country_code'] = $input['country_code'];

        try {
            $dropPoints = $this->send($shippingMethod->shippingCarrier->credential, 'POST', '/drop_point_locators', $this->dropPointLocatorRequestBody($shippingMethod, $deliveryAddress)) ?? [];

            if (empty($dropPoints['data']['attributes']['drop_points'])) {
                throw new \Exception('Trying different method');
            }
        } catch (\Exception $exception) {
            $dropPoints = $this->send($shippingMethod->shippingCarrier->credential, 'POST', '/drop_point_locators', $this->dropPointLocatorRequestBody($shippingMethod, $deliveryAddress, $shippingMethod->settings['external_method_id'])) ?? [];
        }

        $results = [];

        if (Arr::exists($dropPoints, 'data')) {
            foreach ($dropPoints['data']['attributes']['drop_points'] as $dropPoint) {
                $results[] = [
                    'id' => $dropPoint['drop_point_id'],
                    'text' => $dropPoint['name'] . ', ' . $dropPoint['address_1'] . ', ' . $dropPoint['zip'] . ' ' . $dropPoint['city']
                ];
            }
        }

        if (Arr::exists($input, 'preselect')) {
            if ($order->drop_point_id) {
                $extractedDropPointId = $order->drop_point_id;
            } else {
                $matches = [];
                preg_match('/(?<=_)[\d]+/', $order->shipping_method_code, $matches);

                if (isset($matches[0])) {
                    $extractedDropPointId = (int)$matches[0];
                }
            }
        }

        if (Arr::exists($input, 'q')) {
            $search = $input['q'];
            $extractedDropPointId = null;
        }

        if (isset($extractedDropPointId, $results)) {
            $filteredResults = [];

            foreach ($results as $result) {
                if ((int) $result['id'] === $extractedDropPointId) {
                    $filteredResults[] = $result;
                }
            }

            $results = $filteredResults;
        } elseif (isset($search, $results)) {
            $filteredResults = [];

            foreach ($results as $result) {
                if (str_contains(strtolower($result['text']), $search)) {
                    $filteredResults[] = $result;
                }
            }

            $results = $filteredResults;
        }

        return response()->json([
            'results' => $results
        ]);
    }

    private function dropPointLocatorRequestBody(ShippingMethod $shippingMethod, array $deliveryAddress, $shippingRateId = null): array
    {
        $request['data']['type'] = 'drop_point_locators';

        if ($shippingRateId) {
            $request['data']['attributes']['shipping_rate_id'] = $shippingRateId;
        } else {
            $request['data']['attributes']['carrier_id'] = $shippingMethod->shippingCarrier->settings['external_carrier_id'];
            $request['data']['attributes']['service_code'] = '';
        }
        $request['data']['attributes']['delivery_address'] = $deliveryAddress;

        return $request;
    }

    public function void(Shipment $shipment): array
    {
        $shipment->voided_at = Carbon::now();

        $shipment->saveQuietly();

        return ['success' => true, 'message' => __('Shipment successfully voided.')];
    }

    /**
     * @param array $shipmentRequestBody
     * @param ShippingMethod $shippingMethod
     * @param Shipment $shipment
     * @return void
     * @throws ShippingException
     * @throws \JsonException|GuzzleException
     */
    private function createAutoReturnLabels(array $shipmentRequestBody, ShippingMethod $shippingMethod, Shipment $shipment): void
    {
        $autoReturnLabelRequestBody = $this->createAutoReturnLabelRequestBody($shipmentRequestBody, $shippingMethod, $shipment->order);

        $response = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            '/shipments?include=labels',
            $autoReturnLabelRequestBody,
            false
        );

        if ($response) {
            $this->storeShipmentLabelAndTracking($shipment, $response, ShipmentLabel::TYPE_RETURN);
        }
    }

    /**
     * Store custom invoice as document and include document to the shipment
     *
     * @param ShippingMethod $shippingMethod
     * @param Shipment $shipment
     * @return void
     * @throws ShippingException
     * @throws \JsonException|GuzzleException
     */
    private function sendShipmentCustomInvoice(ShippingMethod $shippingMethod, Shipment $shipment): void
    {
        $shipmentCustomInvoiceRequestBody = $this->createShipmentCustomInvoiceDocumentRequestBody($shipment);

        $documentResponse = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            '/documents',
            $shipmentCustomInvoiceRequestBody
        );

        if ($documentResponse) {
            $shipmentRequestBody = $this->includeDocumentForShipmentRequestBody($shipment, $documentResponse);

            $this->send(
                $shippingMethod->shippingCarrier->credential,
                'PATCH',
                '/shipments/' . $shipment->external_shipment_id,
                $shipmentRequestBody
            );
        }
    }

    /**
     * @param $shipmentRequestBody
     * @param ShippingMethod $shippingMethod
     * @return array
     */
    private function createAutoReturnLabelRequestBody($shipmentRequestBody, ShippingMethod $shippingMethod, Order $order): array
    {
        $deliveryAddress = $shipmentRequestBody['data']['attributes']['delivery_address'];

        $customerWarehouseAddress = $order->customer->returnToContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->returnToContactInformation;
        }

        if (!empty($customerWarehouseAddress)) {
            $senderAddress = $this->setAddressForRequest($customerWarehouseAddress, $order);
        } else {
            $senderAddress = $shipmentRequestBody['data']['attributes']['sender_address'];
        }

        $shipmentRequestBody['data']['attributes']['delivery_address'] = $senderAddress;
        $shipmentRequestBody['data']['attributes']['sender_address'] = $deliveryAddress;

        $shipmentRequestBody['data']['relationships']['shipping_rate']['data']['id'] = $shippingMethod->settings['external_method_id'];
        $shipmentRequestBody['data']['relationships']['shipping_rate']['data']['type'] = 'shipping_rates';

        $shipmentRequestBody['data']['relationships']['carrier']['data']['id'] = $shippingMethod->shippingCarrier->settings['external_carrier_id'];
        $shipmentRequestBody['data']['relationships']['carrier']['data']['type'] = 'carriers';

        if (isset($shipmentRequestBody['data']['attributes']['drop_point'])) {
            unset($shipmentRequestBody['data']['attributes']['drop_point']);
        }

        return $shipmentRequestBody;
    }

    /**
     * @param Shipment $shipment
     * @return array
     */
    private function createShipmentCustomInvoiceDocumentRequestBody(Shipment $shipment): array
    {
        $request['data']['type'] = 'documents';

        $request['data']['attributes']['shipment_id'] = $shipment->external_shipment_id;
        $request['data']['attributes']['base64'] = base64_encode(file_get_contents($shipment->order->custom_invoice_url));
        $request['data']['attributes']['document_type'] = 'invoice';
        $request['data']['attributes']['document_format'] = 'PDF';
        $request['data']['attributes']['document_size'] = 'A4';
        $request['data']['attributes']['is_paperless'] = false;

        return $request;
    }

    private function includeDocumentForShipmentRequestBody(Shipment $shipment, $documentResponse): array
    {
        $shipmentRequestBody['data']['type'] = 'shipments';
        $shipmentRequestBody['data']['id'] = $shipment->external_shipment_id;
        $shipmentRequestBody['data']['attributes']['included_documents'][0]['document_id'] = $documentResponse['data']['id'];

        return $shipmentRequestBody;
    }

    public function manifest(ShippingCarrier $shippingCarrier)
    {
        // TODO: Implement manifest() method.
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     */
    public function getShippingRates(Order $order, array $input, array $params = []): array
    {
        $rates = [];

        $packingState = json_decode($input['packing_state'], true);

        foreach ($packingState as $index => $package) {
            //if we have unpacked items and the user requests the shipping rates,
            //we'll simulate packing them into the last package to get the most accurate rate
            if (!empty($package['items']) && array_key_last($packingState) === $index) {
                $package['weight'] += (int) Arr::get($input, 'total_unpacked_weight');
            }

            $rateForPackage = $this->getShippingRateForPackage($order, $input, empty($package['items']) ? [] : $package, $params);

            if (empty($rates)) {
                $rates = $rateForPackage;
            } else {
                foreach ($rateForPackage as $key => $carriers) {
                    foreach ($carriers as $carrier => $service) {
                        if (isset($service['rate'], $rates[$key][$carrier])) {
                            $rates[$key][$carrier]['rate'] += $service['rate'];
                        } else {
                            unset($rates[$key][$carrier]);
                        }
                    }
                }
            }
        }

        return $rates;
    }

     /**
     * @param  Order  $order
     * @param  array  $input
     * @param  array  $package
     * @param  array  $params
     * @return array
     * @throws GuzzleException
     */
    private function getShippingRateForPackage(Order $order, array $input, array $package = [], array $params = []): array
    {
        $shipmentData = $this->prepareShipmentData($order, $input, $package);

        try {
            $response = $this->fetchRates($params['credentials'], $shipmentData, $order);
            $customerIds = [$order->customer_id];

            if ($order->customer->parent_id) {
                $customerIds[] = $order->customer->parent_id;
            }

            if ($response['code'] != 200) {
                return [];
            }

            $rates = [];

            $shippingMethod = ShippingMethod::where('name', self::SHIPPING_METHOD_NAME)
                ->whereHas('shippingCarrier', function ($q) use ($customerIds) {
                    $q->whereIn('customer_id', $customerIds)
                        ->where('carrier_service', self::INTEGRATION_NAME);
                })
                ->first();

            if ($shippingMethod) {
                $rates[$response['data']['plan_id']][] = [
                    'service' => self::SHIPPING_METHOD_NAME,
                    'rate' => $response['data']['final_price'],
                    'currency' => 'BDT',
                    'delivery_days' => '',
                    'shipping_method_id' => $shippingMethod->id
                ];
            } else {
                $shippingCarrier = ShippingCarrier::whereIn('customer_id', $customerIds)
                    ->where('carrier_service', self::INTEGRATION_NAME)
                    ->first();

                if ($shippingCarrier) {
                    $settings = [
                        'shipping_method' => self::SHIPPING_METHOD_NAME,
                        'delivery_type' => self::DELIVERY_TYPE,
                        'item_type' => self::ITEM_TYPE,
                    ];

                    $newShippingMethod = new ShippingMethod([
                        'customer_id' => $shippingCarrier->customer_id,
                        'shipping_carrier_id' => $shippingCarrier->id,
                        'settings' => $settings,
                        'name' => $rate['shipping_rate']['name'],
                        'source' => ShippingMethod::DYNAMICALLY_ADDED
                    ]);

                    $newShippingMethod->save();

                    $rates[$response['data']['plan_id']][] = [
                        'service' => self::SHIPPING_METHOD_NAME,
                        'rate' => $response['data']['final_price'],
                        'currency' => 'BDT',
                        'delivery_days' => '',
                        'shipping_method_id' => $newShippingMethod->id
                    ];
                }
            }

            return $rates;
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
        }

        return [];
    }

    private function prepareShipmentData(Order $order, array $input, array $package): array
    {
        $contactInformationData = $order->shippingContactInformation;
        $deliveryAddress['zip'] = $contactInformationData['zip'];
        $deliveryAddress['country_code'] = $contactInformationData->country->iso_3166_2 ?? null;

        return [
            // "store_id" => $shippingMethod->shippingCarrier->credential->store_id,
            "recipient_city" => $deliveryAddress['city'] ?? 1,   
            "recipient_zone" => $deliveryAddress['zip'],        
            // "delivery_type" => (int) $shippingMethod->settings['delivery_type'], 
            // "item_type" => (int) $shippingMethod->settings['item_type'],        
            "item_weight" => $package['weight'],
        ];

        $items = [];
        $priceTotal = 0;
        $orderItemsToShip = [];
        $itemsPackedInThisPackage = [];
        $tmpCountArr = [];

        foreach($package['items'] as $packItem ) {
            $orderItem = OrderItem::find($packItem['orderItem']);
            $orderItemsToShip[] = ['orderItem' => $orderItem];
            $tmpCountArr[] = $packItem['orderItem'];
        }

        foreach($package['items'] as $packItem ) {
            $packItemId = $packItem['orderItem'];

            foreach ($orderItemsToShip as $orderItemToShip) {
                if ($orderItemToShip['orderItem']->id == $packItemId && !in_array($packItemId, $itemsPackedInThisPackage)) {
                    $itemsPackedInThisPackage[] = $packItemId;

                    $item['sku'] = $orderItemToShip['orderItem']->sku;
                    $tmp = array_count_values($tmpCountArr);
                    $item['quantity'] = $tmp[$packItemId];

                    $unitPrice = $orderItemToShip['orderItem']->priceForCustoms();
                    $priceTotal += $unitPrice * $item['quantity'];

                    $items[] = $item;
                    break;
                }
            }
        }

        

        // $request['data']['type'] = 'rate_quotes';
        // $request['data']['attributes']['price'] = $priceTotal;
        // $request['data']['attributes']['weight'] = $package['weight'];
        // $request['data']['attributes']['delivery_address'] = $deliveryAddress;
        // $request['data']['attributes']['items'] = $items;

        
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     */
    public function getCheapestShippingRates(Order $order, array $input, array $params = []): array
    {
        $rates = [];

        $packingState = json_decode($input['packing_state'], true);

        foreach ($packingState as $index => $package) {
            if (!empty($package['items']) && array_key_last($packingState) === $index) {
                $package['weight'] += (int) Arr::get($input, 'total_unpacked_weight');
            }

            $rateForPackage = $this->getCheapestShippingRateForPackage($order, $input, empty($package['items']) ? [] : $package, $params);

            if (empty($rates)) {
                $rates = $rateForPackage;
            } else {
                foreach ($rateForPackage as $key => $service) {
                    if (isset($service['rate']) && $rates[$key]['service'] === $service['service']) {
                        $rates[$key]['rate'] += $service['rate'];
                    } else {
                        unset($rates[$key]);
                    }
                }
            }
        }

        return $rates;
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $package
     * @param array $params
     * @return array
     * @throws GuzzleException
     */
    private function getCheapestShippingRateForPackage(Order $order, array $input, array $package = [], array $params = []): array
    {
        $shipmentData = $this->prepareShipmentData($order, $input, $package);

        $cheapestRates = [
            'cheapest' => []
        ];

        try {
            $response = $this->fetchRates($params['credentials'], $shipmentData, $order);
            $response = $response[0];
            $customerIds = [$order->customer_id];

            if ($order->customer->parent_id) {
                $customerIds[] = $order->customer->parent_id;
            }

            if ($response['code'] != 200) {
                return [];
            }

            $rates = [];

            $shippingMethod = ShippingMethod::where('name', self::SHIPPING_METHOD_NAME)
                ->whereHas('shippingCarrier', function ($q) use ($customerIds) {
                    $q->whereIn('customer_id', $customerIds)
                        ->where('carrier_service', self::INTEGRATION_NAME);
                })
                ->first();

            if ($shippingMethod) {
                $rates[$response['data']['plan_id']][] = [
                    'service' => self::SHIPPING_METHOD_NAME,
                    'rate' => $response['data']['final_price'],
                    'currency' => 'BDT',
                    'delivery_days' => '',
                    'shipping_method_id' => $shippingMethod->id
                ];
            }

            foreach ($rates as $carrier => $rate) {
                foreach ($rate as $service) {
                    if (isset($service['service']) &&
                        (empty($cheapestRates['cheapest']) || $cheapestRates['cheapest']['rate'] > $service['rate'])
                    ) {
                        $cheapestRates['cheapest'] = [
                            'carrier' => $carrier,
                            'service' => $service['service'],
                            'rate' => $service['rate'],
                            'currency' => $service['currency'],
                            'delivery_days' => '',
                            'shipping_method_id' => $service['shipping_method_id']
                        ];
                    }
                }
            }

            return $cheapestRates;
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
        }

        return [];
    }

    /**
     * @param array $contactInformationData
     * @param Order $order
     * @return array
     */
    public function setAddressForRequest(array $contactInformationData, Order $order): array
    {
        $address['att_contact'] = $contactInformationData['name'];
        $address['company_name'] = $contactInformationData['company_name'] ?? null;
        $address['address_1'] = $contactInformationData['address'];
        $address['address_2'] = $contactInformationData['address2'];
        $address['zip'] = $contactInformationData['zip']; //zone
        $address['city'] = $contactInformationData['city'];
        $address['state'] = $contactInformationData['state']; //area
        $address['country_code'] = $order->shippingContactInformation->country->iso_3166_2 ?? null;
        $address['email'] = $contactInformationData['email'];
        $address['phone'] = $contactInformationData['phone'];

        return $address;
    }

    public function getPathaoShipment(Shipment $shipment)
    {
        return $this->send(
            $shipment->shippingMethod->shippingCarrier->credential,
            'GET',
            '/shipments/' . $shipment->external_shipment_id  . '?include=labels',
        );
    }

    /**
     * @throws GuzzleException
     * @throws ShippingException
     */
    private function fetchRates(Collection $credentials, array $shipmentData, Order $order): array
    {
        $responses = [];

        foreach ($credentials as $credential) {
            $shipmentData['store_id'] = $credential->store_id;
            $shipmentData['item_type'] = self::ITEM_TYPE;
            $shipmentData['delivery_type'] = self::DELIVERY_TYPE;

            $responses[] = $this->send($credential, 'POST', '/merchant/price-plan', $shipmentData);
        }

        return $responses;
    }

    /**
     * @param Package $package
     * @param string $type
     * @return string
     */
    private function generateLabel(Package $package, string $type = ShipmentLabel::TYPE_SHIPPING): string
    {
        $generator = new BarcodeGeneratorPNG();
        $shipFromContactInformation = $package->shipment->order->customer->shipFromContactInformation;

        if (empty($shipFromContactInformation)) {
            $shipFromContactInformation = $package->shipment->order->customer->parent?->shipFromContactInformation;

            if (empty($shipFromContactInformation)) {
                $shipFromContactInformation = $package->packageOrderItems->first()->location->warehouse->contactInformation;
            }
        }

        $data = [
            'senderCustomerContactInformation' => $package->shipment->order->customer->contactInformation,
            'senderContactInformation' => $shipFromContactInformation,
            'receiverCustomerContactInformation' => $package->shipment->contactInformation,
            'receiverContactInformation' => $package->shipment->contactInformation,
            'barcode' => $generator->getBarcode($package->shipment->order->number, $generator::TYPE_CODE_128),
            'barcodeNumber' => $package->shipment->order->number,
            'trackingNumber' => $package->shipment->shipmentTrackings->first()->tracking_number ?? ''
        ];

        $paperWidth = paper_width($package->shipment->order->customer_id, 'label');
        $paperHeight = paper_height($package->shipment->order->customer_id, 'label');

        if ($type === ShipmentLabel::TYPE_RETURN) {
            $returnToContactInformation = $package->shipment->order->customer->returnToContactInformation;

            if (empty($returnToContactInformation)) {
                $returnToContactInformation = $package->shipment->order->customer->parent?->returnToContactInformation;

                if (empty($returnToContactInformation)) {
                    $returnToContactInformation = $data['senderContactInformation'];
                }
            }

            $senderCustomerContactInformation = $data['senderCustomerContactInformation'];
            $receiverCustomerContactInformation = $data['receiverCustomerContactInformation'];
            $receiverContactInformation = $data['receiverContactInformation'];

            $data['senderCustomerContactInformation'] = $receiverCustomerContactInformation;
            $data['senderContactInformation'] = $receiverContactInformation;
            $data['receiverCustomerContactInformation'] = $senderCustomerContactInformation;
            $data['receiverContactInformation'] = $returnToContactInformation;

            return PDF::loadView('pdf.genericlabel', $data)
                ->setPaper([0, 0, $paperWidth, $paperHeight])
                ->output();
        }

        return PDF::loadView('pdf.genericlabel', $data)
            ->setPaper([0, 0, $paperWidth, $paperHeight])
            ->output();
    }
}

