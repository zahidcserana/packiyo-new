<?php

namespace App\Components\Shipping\Providers;

use App\Components\ShippingComponent;
use App\Components\ReturnComponent;
use App\Http\Requests\{FormRequest,
    Packing\PackageItemRequest,
    Packing\BulkShipStoreRequest,
    Shipment\ShipItemRequest,
    TribirdShipping\StoreReturnRequestWithRate
};
use App\Interfaces\{BaseShippingProvider,
    ShippingProviderCredential};
use App\Models\{Customer,
    Currency,
    CustomerSetting,
    ShippingBox,
    TribirdCredential,
    Order,
    OrderItem,
    Shipment,
    ShipmentLabel,
    ShippingCarrier,
    ShippingMethod,
    ShippingMethodMapping,
    ShipmentTracking,
    Return_,
    ContactInformation
};
use Illuminate\Database\{Eloquent\Collection, Eloquent\Model};
use Exception;
use Carbon\Carbon;
use Illuminate\Support\{Arr, Facades\Log, Str};
use setasign\Fpdi\{PdfReader\PageBoundaries, Tcpdf\Fpdi};
use Symfony\Component\HttpKernel\Exception\HttpException;

class TribirdShippingProvider implements BaseShippingProvider
{
    public const SHIPPING_CARRIER_SERVICE = 'tribird';

    public function getAvailableIntegrations(ShippingProviderCredential $credential = null)
    {
        return $this->send($credential, 'GET', '/carriers/active_setup_list');
    }

    public function getIntegration($type)
    {
        return $this->send(null, 'GET', '/carriers/' . $type);
    }

    public function setupCarrierConnection($data)
    {
        $requestBody['endpoint_type'] = $data['carrier_type'];
        $requestBody['configuration'] = $data['configurations'];

        $endpoint = '/carriers/connection_setup?customer_id=' . $data['customer_id'];

        $credential = $this->getCredential($data['customer_id']);

        if ($credential) {
            $endpoint = $endpoint . "&integration_id=" . $credential->settings['external_integration_id'];
        }

        $connection = $this->send($credential, 'POST', $endpoint, $requestBody);

        if (!$connection || $connection['errors']) {
            return null;
        }

        $credential = $this->getCredential($data['customer_id']);

        if (!$credential) {
            $data['settings']['external_integration_id'] = $connection['data']['integration_id'];
            $credential = TribirdCredential::create($data);
        }

        $credential->restore();

        return $this->getTribirdCarriers($credential, $connection);
    }

    private function getCredential($customerId)
    {
        return TribirdCredential::withTrashed()->where('customer_id', $customerId)->first();
    }

    public function getCarriers(ShippingProviderCredential $credential = null) {
        $credentials = new Collection();

        if (!is_null($credential)) {
            $credentials->add($credential);
        } else {
            $credentials = TribirdCredential::all();
        }

        foreach ($credentials as $credential) {
            $shippingCarriers = $credential->shippingCarriers->groupBy('settings.external_dataflow_id');

            foreach ($shippingCarriers as $key => $shippingCarrier) {
                $shippingCarrier = $shippingCarrier->first();

                $connection = $this->getCarrierConnectionDetails($shippingCarrier);

                $this->getTribirdCarriers($credential, $connection);
            }
        }
    }

    private function getTribirdCarriers(TribirdCredential $credential, $connection)
    {
        $carrierService = array_search(get_class($this), ShippingComponent::SHIPPING_CARRIERS);
        $carrierList = $this->getTribirdCarrierList($credential, $connection);

        if (!$carrierList || $carrierList['success'] != true) {
            return null;
        }

        $storedCarrierIds = [];

        foreach ($carrierList['carriers'] as $carrier) {
            $shippingCarrier = ShippingCarrier::withTrashed()
                ->where('customer_id', $credential->customer->id)
                ->where('carrier_service', $carrierService)
                ->where('name', Arr::get($carrier, 'name'))
                ->whereJsonContains('settings', ['external_carrier_id' => Arr::get($carrier, 'id')])
                ->whereJsonContains('settings', ['external_dataflow_id' => $connection['data']['dataflow_id']])
                ->first();

            if (!$shippingCarrier) {
                $shippingCarrier = ShippingCarrier::create([
                    'customer_id' => $credential->customer->id,
                    'carrier_service' => $carrierService,
                    'carrier_account' => $connection['data']['carrier_account'],
                    'name' => Arr::get($carrier, 'name'),
                    'settings' => [
                        'external_dataflow_id' => $connection['data']['dataflow_id'],
                        'external_carrier_id' => Arr::get($carrier, 'id')
                    ]
                ]);

                $shippingCarrier->credential()->associate($credential);

                if (isset($carrier['account_type']) && $carrier['account_type']) {
                    $settings = $shippingCarrier->settings;
                    $settings['carrier_account_type'] = Arr::get($carrier, 'account_type');
                    $shippingCarrier->settings = $settings;
                }
            }

            $shippingCarrier->carrier_account = $connection['data']['carrier_account'] ?? null;
            $shippingCarrier->integration = $connection['data']['name'] ?? null;

            $shippingCarrier->save();
            $shippingCarrier->restore();
            $storedCarrierIds[] = $shippingCarrier->id;

            $storedMethodIds = [];

            foreach ($carrier['shipping_methods'] as $shippingService) {
                $shippingMethod = ShippingMethod::withTrashed()
                    ->where('shipping_carrier_id', $shippingCarrier->id)
                    ->where('name', $shippingService['name'])
                    ->first();

                if (!$shippingMethod) {
                    $shippingMethod = ShippingMethod::create([
                        'shipping_carrier_id' => $shippingCarrier->id
                    ]);
                }

                $shippingMethod->name = $shippingService['name'];

                $shippingMethodSettings = [];

                if (isset($shippingService['id']) && $shippingService['id']) {
                    $shippingMethodSettings['external_method_id'] = $shippingService['id'];
                }

                if (isset($shippingService['has_drop_points']) && $shippingService['has_drop_points']) {
                    $shippingMethodSettings['has_drop_points'] = $shippingService['has_drop_points'];
                }

                if (count($shippingMethodSettings) > 0) {
                    $shippingMethod->settings = $shippingMethodSettings;
                }

                $shippingMethod->save();
                $shippingMethod->restore();

                $storedMethodIds[] = $shippingMethod->id;
            }

            $shippingCarrier->shippingMethods()
                ->withTrashed()
                ->whereNotIn('id', $storedMethodIds)
                ->delete();
        }

        $shippingCarriersToDelete = $credential->shippingCarriers()
            ->withTrashed()
            ->whereNotIn('id', $storedCarrierIds)
            ->whereJsonContains('settings', ['external_dataflow_id' => $connection['data']['dataflow_id']])
            ->get();

        foreach ($shippingCarriersToDelete as $shippingCarrierToDelete) {
            $shippingCarrierToDelete->shippingMethods()->withTrashed()->delete();
            $shippingCarrierToDelete->delete();
        }

        return $carrierList;
    }

    private function getTribirdCarrierList(TribirdCredential $credential, $connection)
    {
        $warehouse = $credential->customer->parent_id ? $credential->customer->parent->warehouses->first() : $credential->customer->warehouses->first();
        $customerWarehouseAddress = $warehouse->contactInformation;

        $requestBody['country'] = $customerWarehouseAddress->country->iso_3166_2;
        $requestBody['reference_prefix'] = null;

        return $this->send($credential, 'POST', '/dataflows/carriers/shipping_carriers/' . $connection['data']['dataflow_id'], $requestBody);
    }

    public function updateCarrierConnection(ShippingCarrier $shippingCarrier, $configurations)
    {
        $requestBody['configuration'] = $this->reformConfigurations($configurations);

        return $this->send($shippingCarrier->credential, 'POST', '/carriers/dataflows/' . $shippingCarrier->settings['external_dataflow_id'] . '/update', $requestBody);
    }

    private function reformConfigurations($configurations) {
        $configs = [];
        foreach ($configurations as $configuration) {
            $configs[] = $configuration;
        }
        return $configs;
    }

    public function deleteCarrierConnection($dataflowId)
    {
        return $this->send(null, 'DELETE', '/carriers/dataflows/' . $dataflowId);
    }

    public function getCarrierConnectionDetails(ShippingCarrier $shippingCarrier)
    {
        return $this->send($shippingCarrier->credential, 'GET', '/carriers/dataflows/' . $shippingCarrier->settings['external_dataflow_id']);
    }

    /**
     * @param Order $order
     * @param FormRequest $storeRequest
     * @param ShippingMethod|null $shippingMethod
     * @return array
     */
    public function ship(Order $order, FormRequest $storeRequest, ShippingMethod $shippingMethod = null): array
    {
        $input = $storeRequest->all();

        if (is_null($shippingMethod)) {
            $shippingRateId = $input['shipping_method_id'];
            $shippingMethod = ShippingMethod::find($shippingRateId);
        }

        $packageItemRequests = [];

        $packingState = json_decode($input['packing_state'], true);

        $shipments = [];

        foreach ($packingState as $packingStateItem) {
            $orderItemsToShip = [];

            foreach ($packingStateItem['items'] as $packingStateOrderItem) {
                $orderItemId = Arr::get($packingStateOrderItem, 'orderItem');
                $locationId = Arr::get($packingStateOrderItem, 'location');
                $toteId = Arr::get($packingStateOrderItem, 'tote');
                $serialNumber = Arr::get($packingStateOrderItem, 'serialNumber');
                $shipItemRequestKey = $orderItemId . '_' . $locationId . '_' . $toteId . '_' . $serialNumber;

                $orderItem = OrderItem::find($orderItemId);

                if (!isset($orderItemsToShip[$shipItemRequestKey])) {
                    if ($toteId === 0) {
                        $toteId = null;
                    }

                    $shipItemRequest = ShipItemRequest::make([
                        'order_item_id' => $orderItemId,
                        'location_id' => $locationId,
                        'tote_id' => $toteId,
                        'quantity' => 1,
                        'serial_number' => $serialNumber,
                    ]);

                    $orderItemsToShip[$shipItemRequestKey] = [
                        'orderItem' => $orderItem,
                        'shipRequest' => $shipItemRequest
                    ];
                } else {
                    $shipItemRequestData = $orderItemsToShip[$shipItemRequestKey]['shipRequest']->all();
                    $shipItemRequestData['quantity']++;

                    $orderItemsToShip[$shipItemRequestKey]['shipRequest'] = ShipItemRequest::make($shipItemRequestData);
                }
            }

            $packageItemRequest = PackageItemRequest::make($packingStateItem);
            $packageItemRequests[] = $packageItemRequest;

            $requestedLabelFormat = customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_USE_ZPL_LABELS) ? 'zpl' : 'pdf';

            if (get_class($storeRequest) === BulkShipStoreRequest::class) {
                $requestedLabelFormat = 'pdf';
            }

            $shipmentRequestBody = $this->createShipmentRequestBody($order, $storeRequest, $packageItemRequest->all(), $shippingMethod, $requestedLabelFormat);

            $response = $this->send(
                $shippingMethod->shippingCarrier->credential,
                'POST',
                '/dataflows/carriers/create_booking/' . $shippingMethod->shippingCarrier->settings['external_dataflow_id'],
                $shipmentRequestBody
            );

            if ($response && $response['response_list']) {
                $response = $response['response_list'];
            } else if ($response && $response['errors'] && count($response['errors']) > 0) {
                throw new HttpException(500, $response['errors'][0]);
            }

            if (!empty($response)) {
                $shipments[] = $this->createShipment($response[0], $order, $shippingMethod, $input, $orderItemsToShip, $packageItemRequest, $packingStateItem, $storeRequest, $requestedLabelFormat);
            }
        }

        return $shipments;
    }

    private function createShipment($shipmentResponse, Order $order, $shippingMethod, $input, $orderItemsToShip, $packageItemRequest, $packingStateItem, $storeRequest, $requestedLabelFormat): ?Shipment
    {
        $shipment = app('shipping')->createShipment($order, $shippingMethod, $input, Arr::get($shipmentResponse, 'cost', 0) ?? 0, $shipmentResponse['reference_id']);

        app('shipment')->createContactInformation($order->shippingContactInformation->toArray(), $shipment);

        foreach ($orderItemsToShip as $orderItemToShip) {
            app('shipment')->shipItem($orderItemToShip['shipRequest'], $orderItemToShip['orderItem'], $shipment);
        }

        if ($order->shipments->count() === 1) {
            app('shipment')->shipVirtualProducts($order, $shipment);
        }

        app('shipping')->createPackage($order, $packageItemRequest, $shipment);

        $this->storeShipmentLabelAndTracking($shipment, $shipmentResponse, $requestedLabelFormat);

        if (customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_AUTO_RETURN_LABEL) === '1') {
            $mappedShippingMethod = app('shippingMethodMapping')->returnShippingMethod($order) ?? $shippingMethod;

            if ($shippingMethod->shippingCarrier->settings['external_dataflow_id'] == $mappedShippingMethod->shippingCarrier->settings['external_dataflow_id']) {
                $this->createAutoReturnLabels($packingStateItem, $order, $storeRequest, $mappedShippingMethod, $shipment, $requestedLabelFormat);
            }
        }

        return $shipment;
    }

    private function createAutoReturnLabels(mixed $packingStateItem, Order $order, FormRequest $storeRequest, ShippingMethod $shippingMethod, Shipment $shipment, $requestedLabelFormat): void
    {
        $packageItemRequest = PackageItemRequest::make($packingStateItem);

        $shipmentRequestBody = $this->createShipmentRequestBody($order, $storeRequest, $packageItemRequest->all(), $shippingMethod, $requestedLabelFormat);
        $shipmentRequestBody = $this->swapAddresses($shipmentRequestBody, $order);
        $shipmentRequestBody['auto_return'] = true;

        $response = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            '/dataflows/carriers/create_booking/' . $shippingMethod->shippingCarrier->settings['external_dataflow_id'],
            $shipmentRequestBody
        );

        if ($response && $response['response_list'] && count($response['response_list']) > 0) {
            $carrierResponse = $response['response_list'][0];

            app('shipping')->storeShipmentLabel(
                $shipment,
                $this->labelContent($shipment, $carrierResponse, $requestedLabelFormat),
                Arr::get($carrierResponse, 'label_size') ?? '',
                Arr::get($carrierResponse, 'label_url'),
                ShipmentLabel::TYPE_RETURN,
                $carrierResponse['label_content_type'] != null ? $requestedLabelFormat : null
            );

            app('shipping')->storeShipmentTracking(
                $shipment,
                Arr::get($carrierResponse, 'tracking_number'),
                Arr::get($carrierResponse, 'tracking_url'),
                ShipmentTracking::TYPE_RETURN
            );
        }
    }

    public function createShipmentRequestBody(Order $order, FormRequest $storeRequest, array $packageItemInput, ShippingMethod $shippingMethod, $requestedLabelFormat)
    {
        $customerAddress = $order->customer->contactInformation;
        $customerWarehouseAddress = $order->customer->shipFromContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->shipFromContactInformation;

            if (empty($customerWarehouseAddress)) {
                $warehouse = $order->warehouse;

                if (!$warehouse) {
                    $warehouse = app('packing')->getSenderWarehouse($order, $packageItemInput);
                }

                $customerWarehouseAddress = $warehouse->contactInformation;
                $senderName = $customerAddress->name;
            } else {
                $senderName = $customerWarehouseAddress->name;
            }
        } else {
            $senderName = $customerWarehouseAddress->name;
        }

        $request['receiver'] = $this->getAddressForRequest($order->shippingContactInformation);
        $request['sender'] = $this->getAddressForRequest($customerWarehouseAddress, $senderName);
        $request['parcel_information'] = $this->getParcelInformationForRequest($order, $packageItemInput, false);
        $request['dimensions_unit'] = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT);
        $request['weight_unit'] = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT);
        $request['reference_number'] = $order->number;
        $request['reference_id'] = $order->id;
        $request['package_items'] = $this->getPackageItemsForRequest($packageItemInput);
        $request['store_name'] = $order->orderChannel->name ?? 'Packiyo';
        $request['shipping_carrier'] = $this->getShippingCarrierInformationForRequest($shippingMethod);
        $request['shipping_method'] = $this->getShippingMethodInformationForRequest($shippingMethod);
        $request['customs_information'] = $this->getCustomsInformationForRequest($order);
        $request['additional_information'] = $this->getAdditionalInformationForRequest($order, $shippingMethod, $requestedLabelFormat);
        $request['shipping_rate_information'] = $this->getShippingRateInformation($storeRequest->all());
        $request['billing_address'] = $this->getAddressForRequest($order->billingContactInformation);

        return $request;
    }

    /**
     * @param Shipment $shipment
     * @param $carrierResponse
     * @param $requestedLabelFormat
     * @return void
     */
    private function storeShipmentLabelAndTracking(Shipment $shipment, $carrierResponse, $requestedLabelFormat): void
    {
        app('shipping')->storeShipmentLabel(
            $shipment,
            $this->labelContent($shipment, $carrierResponse, $requestedLabelFormat),
            Arr::get($carrierResponse, 'label_size') ?? '',
            Arr::get($carrierResponse, 'label_url'),
            ShipmentLabel::TYPE_SHIPPING,
            $carrierResponse['label_content_type'] == 'zpl' && $requestedLabelFormat == 'pdf' ? $requestedLabelFormat : $carrierResponse['label_content_type']
        );

        app('shipping')->storeShipmentTracking(
            $shipment,
            Arr::get($carrierResponse, 'tracking_number'),
            Arr::get($carrierResponse, 'tracking_url'),
            ShipmentTracking::TYPE_SHIPPING
        );
    }

    private function labelContent(Shipment $shipment, array $carrierResponse, string $requestedLabelFormat)
    {
        $labelContent = null;

        if ((($carrierResponse['label_url'] || $carrierResponse['label_content']) && $carrierResponse['label_content_type'] == 'pdf') || ($carrierResponse['label_content_type'] === 'zpl' && $requestedLabelFormat !== $carrierResponse['label_content_type'])) {
            $labelContent = base64_encode($this->getLabelContent($shipment, $carrierResponse, $requestedLabelFormat));
        } else if ($carrierResponse['label_content']) {
            $labelContent = $carrierResponse['label_content'];
        }

        return $labelContent;
    }

    private function send(TribirdCredential $tribirdCredential = null, $method, $endpoint, $data = null, $returnException = false)
    {
        Log::info('[Tribird] send', [
            'tribird_credential_id' => $tribirdCredential->id ?? null,
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data
        ]);

        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        $url = config('tribird.base_url') . $endpoint;

        try {
            $response = $client->request($method, $url, $method == 'GET' ? [] : ['body' => json_encode($data)]);
            $body = json_decode($response->getBody()->getContents() ?? null, true);

            Log::info('[Tribird] response', [$body]);

            return $body;

        } catch (\Exception $exception) {
            Log::error('[Tribird] exception thrown', [$exception]);

            if ($returnException) {
                $response = $exception->getResponse();

                return $response->getBody()->getContents();
            }
        }

        return null;
    }

    private function getCustomerSettingsProperty(Customer $customer, $key)
    {
        if ($contentsSigner = customer_settings($customer->id, $key)) {
            return $contentsSigner;
        }

        if ($customer->parent_id) {
            if ($contentsSigner = customer_settings($customer->parent_id, $key)) {
                return $contentsSigner;
            }
        }
    }

    private function mapDeliveryConfirmation(Order $order)
    {
        switch ($order->delivery_confirmation) {
            case Order::DELIVERY_CONFIRMATION_SIGNATURE:
                return 'SIGNATURE';
            case Order::DELIVERY_CONFIRMATION_ADULT_SIGNATURE:
                return 'ADULT_SIGNATURE';
            case Order::DELIVERY_CONFIRMATION_NO_SIGNATURE:
                return 'NO_SIGNATURE';
            default:
                return null;
        }
    }

    private function getLabelContent(Model $object, $carrierResponse, $requestedLabelFormat): string
    {
        try {
            $carrierAccountType = Arr::get($object->shippingMethod->shippingCarrier->settings ?? [], 'carrier_account_type');

            $labelWidth = paper_width($object->order->customer_id, 'label');
            $labelHeight = paper_height($object->order->customer_id, 'label');

            if ($carrierResponse['label_content'] != null && !empty($carrierResponse['label_content'])) {
                $labelContent = base64_decode($carrierResponse['label_content']);
            } else {
                $labelContent = file_get_contents($carrierResponse['label_url']);
            }

            if ($carrierResponse['label_content_type'] === 'zpl' && $requestedLabelFormat !== $carrierResponse['label_content_type']) {
                $zplContent = file_get_contents($carrierResponse['label_url']);
                $labelContent = app('zplConverter')->convert($zplContent) ?? null;
            }

            $tmpLabelPath = tempnam(sys_get_temp_dir(), 'label');

            file_put_contents($tmpLabelPath, $labelContent);

            $fpdi = new Fpdi('P', 'pt', [$labelWidth, $labelHeight]);
            $fpdi->setPrintHeader(false);
            $fpdi->setPrintFooter(false);

            $pageCount = $fpdi->setSourceFile($tmpLabelPath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $fpdi->AddPage();
                $tplId = $fpdi->importPage($i, PageBoundaries::ART_BOX);

                if (Str::startsWith($carrierAccountType, ['DhlExpress'])) {
                    $size = $fpdi->getTemplateSize($tplId, null, $labelHeight);
                    $size['x'] = -10;
                } else if (Str::startsWith($carrierAccountType, ['Fedex'])) {
                    $size = $fpdi->getTemplateSize($tplId, $labelWidth * 1.85);
                    $size['x'] = -5;
                } else if ($object?->shippingMethod?->shippingCarrier?->name == 'FlavorCloud') {
                    $size = $fpdi->getTemplateSize($tplId, $labelWidth);
                } else if ($object?->shippingMethod?->shippingCarrier?->integration == 'New Zealand Couriers') {
                    $size = $fpdi->getTemplateSize($tplId, $labelWidth);
                } else if ($object?->shippingMethod?->shippingCarrier?->name == 'uShip') {
                    $tplId = $fpdi->importPage($i, PageBoundaries::MEDIA_BOX);
                    $size = $fpdi->getTemplateSize($tplId, $labelWidth);
                } else if ($object?->shippingMethod?->shippingCarrier?->name == 'OpenBorder') {
                    $size = $fpdi->getTemplateSize($tplId, $labelWidth);
                } else {
                    $size = $fpdi->getTemplateSize($tplId);
                }

                $fpdi->useTemplate($tplId, $size);
            }

            return $fpdi->Output('label.pdf', 'S');
        } catch (Exception $exception) {
            Log::error('[Tribird] getLabelContent', [$exception->getMessage()]);
            return '';
        }
    }

    public function void(Shipment $shipment): array
    {
        $request['carrier_id'] = $shipment->shippingMethod->shippingCarrier->settings['external_carrier_id'] ?? '';

        $response = $this->send(
            $shipment->shippingMethod->shippingCarrier->credential,
            'POST',
            '/dataflows/carriers/void_booking/' . $shipment->shippingMethod->shippingCarrier->settings['external_dataflow_id'] . '/' . $shipment->external_shipment_id,
            $request
        );

        if ($response && $response['success']) {
            $shipment->voided_at = Carbon::now();

            $shipment->saveQuietly();

            return ['success' => true, 'message' => __('Shipment successfully voided.')];
        }

        return ['success' => false, 'message' => __('Something went wrong!')];
    }

    public function return(Order $order, $storeRequest): ?Return_
    {
        $input = $storeRequest->validated();

        $input['number'] = Return_::getUniqueIdentifier(ReturnComponent::NUMBER_PREFIX, $input['warehouse_id']);

        $shippingRateId = $input['shipping_method_id'];
        $shippingMethod = ShippingMethod::find($shippingRateId);

        $defaultBox = $order->getDefaultShippingBox();

        $orderItemsArr = [];
        $totalWeight = 0;

        foreach ($input['order_items'] as $record)
        {
            for ($i = 0; $i < $record['quantity']; $i++) {
                $orderItem = OrderItem::find($record['order_item_id']);
                $totalWeight += $orderItem->weight;
                $orderItemsArr[] = [
                    'orderItem' => $record['order_item_id'],
                    'serialNumber' => '',
                    'packedParentKey' => ''
                ];
            }
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

        $requestedLabelFormat = customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_USE_ZPL_LABELS) ? 'zpl' : 'pdf';

        if (Arr::get($storeRequest, 'bulk_ship_batch_id')) {
            $requestedLabelFormat = 'pdf';
        }

        $rateRequestBody = $this->createShippingRateRequestBody($order, $input, $packageItemRequest->all());
        $rateRequestBody = $this->swapAddresses($rateRequestBody, $order);

        $ratesResponse = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            '/dataflows/carriers/shipping_rates/' . $shippingMethod->shippingCarrier->settings['external_dataflow_id'],
            $rateRequestBody
        );

        foreach ($ratesResponse['shipping_rates'] as $rate) {
            if ($rate['carrier'] == $shippingMethod->shippingCarrier->name && $rate['service'] == $shippingMethod->name) {
                $input['rate'] = $rate['rate'];
                $input['rate_id'] = $rate['id'];

                break;
            }
        }

        $storeRequestWithRate = StoreReturnRequestWithRate::make($input);

        $shipmentRequestBody = $this->createShipmentRequestBody($order, $storeRequestWithRate, $packageItemRequest->all(), $shippingMethod, $requestedLabelFormat);
        $shipmentRequestBody = $this->swapAddresses($shipmentRequestBody, $order);

        $response = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            '/dataflows/carriers/create_booking/' . $shippingMethod->shippingCarrier->settings['external_dataflow_id'],
            $shipmentRequestBody
        );

        if ($response && $response['response_list']) {
            $response = $response['response_list'];
        }

        if (!empty($response)) {
            $return = app('return')->createReturn($order, $input);

            $this->storeReturnLabelAndTracking($return, $response[0], $requestedLabelFormat);

            return $return;
        }

        return null;
    }

    /**
     * @param Return_ $return
     * @param $carrierResponse
     * @param $requestedLabelFormat
     * @return void
     */
    private function storeReturnLabelAndTracking(Return_ $return, $carrierResponse, $requestedLabelFormat): void
    {
        app('return')->storeReturnLabel(
            $return,
            base64_encode($this->getLabelContent($return, $carrierResponse, $requestedLabelFormat)),
            Arr::get($carrierResponse, 'label_size'),
            Arr::get($carrierResponse, 'label_url'),
            $carrierResponse['label_content_type'] == 'zpl' && $requestedLabelFormat == 'pdf' ? $requestedLabelFormat : $carrierResponse['label_content_type']
        );

        app('return')->storeReturnTracking(
            $return,
            Arr::get($carrierResponse, 'tracking_number'),
            Arr::get($carrierResponse, 'tracking_url')
        );
    }

    /**
     * @param array $input
     * @return array
     */
    private function swapAddresses(array $input, Order $order): array
    {
        $customerWarehouseAddress = $order->customer->returnToContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->returnToContactInformation;
        }

        if (!empty($customerWarehouseAddress)) {
            $customerAddress = $order->customer->contactInformation;
            $returnToAddress = $this->getAddressForRequest($customerWarehouseAddress, $customerAddress->name);
        } else {
            $returnToAddress = $input['sender'];
        }

        $input['sender'] = $input['receiver'];
        $input['receiver'] = $returnToAddress;

        return $input;
    }

    public function manifest(ShippingCarrier $shippingCarrier)
    {
        return null;
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
                foreach ($rates as $carrierName => $services) {
                    foreach ($rateForPackage as $newCarrierName => $newServices) {
                        if ($carrierName == $newCarrierName) {
                            foreach ($services as $serviceKey => $service) {
                                $serviceFound = false;

                                foreach ($newServices as $newServiceKey => $newService) {
                                    if ($service['service'] == $newService['service']) {
                                        $rates[$carrierName][$serviceKey]['rate'] += $newService['rate'];

                                        $serviceFound = true;
                                    }
                                }

                                if (!$serviceFound) {
                                    unset($rates[$carrierName][$serviceKey]);
                                }
                            }
                        }
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
     */
    private function getShippingRateForPackage(Order $order, array $input, array $package = [], array $params = []): array
    {
        $rates = [];
        $rateRequestBody = $this->createShippingRateRequestBody($order, $input, $package);

        try {
            $credentialIds = isset($params['credentials']) && !empty($params['credentials']) ? $params['credentials']->pluck('id') : [];
            $dataflows = ShippingCarrier::where('carrier_service', self::SHIPPING_CARRIER_SERVICE)->whereIn('credential_id', $credentialIds)->get()->pluck('settings.external_dataflow_id')->unique();

            foreach ($dataflows as $dataflow) {
                $response = $this->send(
                    null,
                    'POST',
                    '/dataflows/carriers/shipping_rates/' . $dataflow,
                    $rateRequestBody
                );

                if ($response && !empty($response['shipping_rates'])) {
                    $carriers = [];

                    $customerIds = [$order->customer_id];

                    if ($order->customer->parent_id) {
                        $customerIds[] = $order->customer->parent_id;
                    }

                    foreach ($response['shipping_rates'] as $rate) {
                        $rateCarrierName = Arr::get($rate, 'carrier');

                        $shippingCarrier = Arr::get($carriers, $dataflow . '.' . $rateCarrierName);

                        if (!$shippingCarrier) {
                            $shippingCarrier = ShippingCarrier::whereIn('customer_id', $customerIds)
                                ->where('name', $rateCarrierName)
                                ->whereJsonContains('settings', ['external_dataflow_id' => $dataflow])
                                ->first();
                        }

                        if ($shippingCarrier) {
                            Arr::add($carriers, $dataflow . '.' . $rateCarrierName, $shippingCarrier);;

                            $carrierNameAndIntegration = $shippingCarrier->getNameAndIntegrationAttribute();

                            $shippingMethod = $shippingCarrier->shippingMethods()
                                ->where('name', $rate['service'])
                                ->first();

                            if ($shippingMethod) {
                                $rates[$carrierNameAndIntegration][] = $this->shippingRate($rate, $shippingMethod->id);
                            } else {
                                $newShippingMethod = new ShippingMethod([
                                    'shipping_carrier_id' => $shippingCarrier->id,
                                    'settings' => ['external_method_id' => $rate['service_id']],
                                    'name' => $rate['service'],
                                    'source' => ShippingMethod::DYNAMICALLY_ADDED
                                ]);

                                $newShippingMethod->save();

                                $rates[$carrierNameAndIntegration][] = $this->shippingRate($rate, $newShippingMethod->id);
                            }
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
        }

        return $rates;
    }

    private function shippingRate($rate, $shippingMehtodId) {
        return [
            'rate_id' => $rate['id'],
            'service' => $rate['service'],
            'rate' => $rate['rate'] ?? 0,
            'currency' => $rate['currency'] ?? 'USD',
            'delivery_days' => $rate['delivery_days'],
            'shipping_method_id' => $shippingMehtodId
        ];
    }

    public function createShippingRateRequestBody(Order $order, array $input, $package)
    {
        $customerAddress = $order->customer->contactInformation;
        $customerWarehouseAddress = $order->customer->shipFromContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->shipFromContactInformation;

            if (empty($customerWarehouseAddress)) {
                $warehouse = $order->warehouse;

                if (!$warehouse) {
                    $warehouse = app('packing')->getSenderWarehouse($order, $package);
                }

                $customerWarehouseAddress = $warehouse->contactInformation;
                $senderName = $customerAddress->name;
            } else {
                $senderName = $customerWarehouseAddress->name;
            }
        } else {
            $senderName = $customerWarehouseAddress->name;
        }

        if ($deliveryAddress = Arr::get($input, 'shipping_contact_information')) {
            $toAddress = new ContactInformation($deliveryAddress);
        } else {
            $toAddress = $order->shippingContactInformation;
        }

        $request['receiver'] = $this->getAddressForRequest($toAddress);
        $request['sender'] = $this->getAddressForRequest($customerWarehouseAddress, $senderName);
        $request['parcel_information'] = $this->getParcelInformationForRequest($order, $package, true);
        $request['dimensions_unit'] = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT);
        $request['weight_unit'] = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT);
        $request['reference_number'] = $order->number;
        $request['reference_id'] = $order->id;
        $request['package_items'] = $this->getPackageItemsForRequest($package);
        $request['store_name'] = $order->orderChannel->name ?? 'Packiyo';
        $request['customs_information'] = $this->getCustomsInformationForRequest($order);
        $request['additional_information'] = $this->getAdditionalInformationForRequest($order, null);

        return $request;
    }

    /**
     * @param array $package
     * @return array
     */
    private function getPackageItemsForRequest(array $package): array
    {
        $customsItems = [];

        if (!empty($package)) {
            foreach ($package['items'] as $packageItem) {
                if (!isset($packageItems[$packageItem['orderItem']])) {
                    $packageItems[$packageItem['orderItem']] = 0;
                }

                $packageItems[$packageItem['orderItem']]++;
            }

            foreach ($packageItems as $orderItemId => $quantity) {
                $orderItem = OrderItem::find($orderItemId);

                if ($orderItem) {
                    $customsItems[] = $this->getPackageItemForRequest($orderItem, $quantity);
                }
            }

            $request['package_items'] = $customsItems;
        }

        return $customsItems;
    }

    /**
     * @param Order $order
     * @return string
     */
    private function getCurrencyForRequest(Order $order): string
    {
        if ($order->currency) {
            return $order->currency->code;
        } else {
            $customerCurrency = Currency::find(customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_CURRENCY));

            if ($customerCurrency) {
                return $customerCurrency->code;
            }
        }

        return 'USD';
    }

    /**
     * @param OrderItem $orderItem
     * @param int $quantity
     * @return array
     */
    private function getPackageItemForRequest(OrderItem $orderItem, int $quantity): array
    {
        return [
            'sku' => substr($orderItem->sku, 0, 20),
            'name' => substr($orderItem->name, 0, 50),
            'quantity' => $quantity,
            'price' => max(1, $orderItem->priceForCustoms()),
            'unit_price' => max(1, $orderItem->priceForCustoms()),
            'currency' => $this->getCurrencyForRequest($orderItem->order),
            'weight' => (string) max(0.01, $orderItem->weight),
            'height' => $orderItem->height,
            'length' => $orderItem->length,
            'width' => $orderItem->width,
            'hs_tariff_number' => $orderItem->product->hs_code,
            'origin_country' => $orderItem->product->country->iso_3166_2 ?? 'US',
            'image' => $orderItem->product->productImages[0]->source ?? '',
            'url' => $orderItem->product->productImages[0]->source ?? asset('img/no-image.png')
        ];
    }

    /**
     * @param Order $order
     * @param ShippingMethod|null $shippingMethod
     * @return array
     */
    private function getAdditionalInformationForRequest(Order $order, ShippingMethod $shippingMethod = null, $requestedLabelFormat = 'pdf'): array
    {
        return [
            'label_format' => $requestedLabelFormat,
            'customer_name' => $order->customer->contactInformation->name ?? '',
            'delivery_confirmation' => $this->mapDeliveryConfirmation($order) ?? null,
            'incoterm' => $order->incoterms ?? $shippingMethod->incoterms ?? null,
            'saturday_delivery' => $order->saturday_delivery ?? true ?? false
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    private function getCustomsInformationForRequest(Order $order): array
    {
        return [
            'customs_certify' => 'true',
            'customs_signer' => $this->getCustomerSettingsProperty($order->customer, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_SIGNER),
            'contents_type' => $this->getCustomerSettingsProperty($order->customer, CustomerSetting::CUSTOMER_SETTING_CONTENTS_TYPE),
            'contents_description' => $this->getCustomerSettingsProperty($order->customer, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_DESCRIPTION),
            'eel_pfc' => $this->getCustomerSettingsProperty($order->customer, CustomerSetting::CUSTOMER_SETTING_EEL_PFC),
            'restriction_type' => 'none'
        ];
    }

    /**
     * @param Order $order
     * @param array $package
     * @param bool $shippingRateRequest
     * @return array
     */
    private function getParcelInformationForRequest(Order $order, array $package, bool $shippingRateRequest): array
    {
        $parcel = [];

        if (empty($package) && $shippingRateRequest) {
            $shippingBox = $order->getDefaultShippingBox();

            $parcel['shipping_box_name'] = $shippingBox->name;
            $parcel['length'] = $shippingBox->length;
            $parcel['width'] = $shippingBox->width;
            $parcel['height'] = $shippingBox->height;
            $parcel['weight'] = max(0.01, $shippingBox->weight);

            foreach ($order->orderItems as $item) {
                $parcel['weight'] += $item->weight * $item->quantity_allocated_pickable;
            }
        } else {
            $parcel['shipping_box_name'] = ShippingBox::find($package['box'])->name;
            $parcel['length'] = (string) max(0.01, $package['_length']);
            $parcel['width'] = (string) max(0.01, $package['width']);
            $parcel['height'] = (string) max(0.01, $package['height']);
            $parcel['weight'] = (string) max(0.01, $package['weight']);
        }

        return $parcel;
    }

    /**
     * @param ContactInformation $contactInformation
     * @param string|null $name
     * @return array
     */
    private function getAddressForRequest(ContactInformation $contactInformation, string $name = null): array
    {
        $address = [];

        if (!$name) {
            $name = $contactInformation->name;
        }

        $address['name'] = $name;
        $address['street'] = $contactInformation->address;
        $address['street2'] = $contactInformation->address2;
        $address['city'] = $contactInformation->city;
        $address['state'] = $contactInformation->state;
        $address['zip'] = $contactInformation->zip;
        $address['country'] = $contactInformation->country->iso_3166_2 ?? null;
        $address['phone'] = $contactInformation->phone;
        $address['email'] = $contactInformation->email;
        $address['company_name'] = $contactInformation->company_name;
        $address['company_number'] = $contactInformation->company_number;

        return $address;
    }

    /**
     * @param ShippingMethod $shippingMethod
     * @return array
     */
    private function getShippingCarrierInformationForRequest(ShippingMethod $shippingMethod): array
    {
        return [
            'id' => $shippingMethod->shippingCarrier->settings['external_carrier_id'] ?? null,
            'name' => $shippingMethod->shippingCarrier->name,
            'account_type' => $shippingMethod->shippingCarrier->settings['carrier_account_type'] ?? null
        ];
    }

    /**
     * @param ShippingMethod $shippingMethod
     * @return array
     */
    private function getShippingMethodInformationForRequest(ShippingMethod $shippingMethod): array
    {
        return [
            'id' => $shippingMethod->settings['external_method_id'] ?? null,
            'name' => $shippingMethod->name,
            'incoterms' => $shippingMethod->incoterms,
            'has_drop_points' => $shippingMethod->settings['has_drop_points'] ?? null
        ];
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     */
    public function getCheapestShippingRates(Order $order, array $input, array $params = []): array
    {
        $cheapestRates = [];

        foreach (ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS as $key => $cheapestShippingMethod) {
            $cheapestRates[$key] = [];
        }

        $rates = $this->getShippingRates($order, $input, $params);

        foreach ($rates as $carrier => $carrierRates) {
            foreach ($carrierRates as $rate) {
                if (isset($rate['service']) && (empty($cheapestRates['cheapest-1day']) || $cheapestRates['cheapest-1day']['rate'] > $rate['rate']) && $rate['delivery_days'] == 1) {
                    $cheapestRates['cheapest-1day'] = array_merge($rate, ['carrier' => $carrier]);
                }

                if (isset($rate['service']) && (empty($cheapestRates['cheapest-2days']) || $cheapestRates['cheapest-2days']['rate'] > $rate['rate']) && $rate['delivery_days'] == 2) {
                    $cheapestRates['cheapest-2days'] = array_merge($rate, ['carrier' => $carrier]);
                }

                if (isset($rate['service']) && (empty($cheapestRates['cheapest-1-3days']) || $cheapestRates['cheapest-1-3days']['rate'] > $rate['rate']) && $rate['delivery_days'] >= 1 && $rate['delivery_days'] <= 3) {
                    $cheapestRates['cheapest-1-3days'] = array_merge($rate, ['carrier' => $carrier]);
                }

                if (isset($rate['service']) && (empty($cheapestRates['cheapest-3-5days']) || $cheapestRates['cheapest-3-5days']['rate'] > $rate['rate']) && $rate['delivery_days'] >= 3 && $rate['delivery_days'] <= 5) {
                    $cheapestRates['cheapest-3-5days'] = array_merge($rate, ['carrier' => $carrier]);
                }

                if (isset($rate['service']) && (empty($cheapestRates['cheapest']) || $cheapestRates['cheapest']['rate'] > $rate['rate'])) {
                    $cheapestRates['cheapest'] = array_merge($rate, ['carrier' => $carrier]);
                }
            }
        }

        return $cheapestRates;
    }

    /**
     * @param $input
     * @return array
     */
    private function getShippingRateInformation($input): array
    {
        return [
            'rate' => Arr::get($input, 'rate'),
            'rate_id' => Arr::get($input, 'rate_id')
        ];
    }
}
