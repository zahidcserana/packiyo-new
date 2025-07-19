<?php

namespace App\Components\Shipping\Providers;

use App\Components\ReturnComponent;
use App\Components\ShippingComponent;
use App\Exceptions\ShippingException;
use App\Http\Requests\{Easypost\CarrierAccount\CreateAccountRequest,
    Easypost\CarrierAccount\DeleteAccountRequest,
    Easypost\CarrierAccount\UpdateAccountRequest,
    FormRequest,
    Packing\PackageItemRequest,
    Shipment\ShipItemRequest};
use App\Interfaces\BaseShippingProvider;
use App\Interfaces\ShippingProviderCredential;
use App\Models\{ContactInformation,
    Currency,
    Customer,
    CustomerSetting,
    EasypostCredential,
    Order,
    OrderItem,
    Package,
    PackageDocument,
    Return_,
    Shipment,
    ShipmentLabel,
    ShipmentTracking,
    ShippingBox,
    ShippingCarrier,
    ShippingMethod,
    ShippingMethodMapping};
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\{Eloquent\Builder, Eloquent\Collection, Eloquent\Model};
use Illuminate\Support\{Arr, Facades\App, Facades\Log, Str};
use setasign\Fpdi\{PdfReader\PageBoundaries, Tcpdf\Fpdi};
use Symfony\Component\HttpKernel\Exception\HttpException;

class EasypostShippingProvider implements BaseShippingProvider
{
    public const BASE_URL = 'https://api.easypost.com/v2';
    public const EASYPOST_BILLING_TYPE = 'easypost';
    public const INTEGRATION_NAME = 'EasyPost';

    private const CARRIER_PREDEFINED_PACKAGES = [
        'FedexAccount' => [
            'FedExEnvelope',
            'FedExBox',
            'FedExPak',
            'FedExTube',
            'FedEx10kgBox',
            'FedEx25kgBox',
            'FedExSmallBox',
            'FedExMediumBox',
            'FedExLargeBox',
            'FedExExtraLargeBox'
        ],
        'FedexDefaultAccount' => [
            'FedExEnvelope',
            'FedExBox',
            'FedExPak',
            'FedExTube',
            'FedEx10kgBox',
            'FedEx25kgBox',
            'FedExSmallBox',
            'FedExMediumBox',
            'FedExLargeBox',
            'FedExExtraLargeBox'
        ],
        'UspsAccount' => [
            'Card',
            'Letter',
            'Flat',
            'FlatRateEnvelope',
            'FlatRateLegalEnvelope',
            'FlatRatePaddedEnvelope',
            'FlatRateWindowEnvelope',
            'FlatRateCardboardEnvelope',
            'SmallFlatRateEnvelope',
            'Parcel',
            'SoftPack',
            'SmallFlatRateBox',
            'MediumFlatRateBox',
            'LargeFlatRateBox',
            'LargeFlatRateBoxAPOFPO',
            'FlatTubTrayBox',
            'EMMTrayBox',
            'FullTrayBox',
            'HalfTrayBox',
            'PMODSack'
        ],
        'UspsDefaultAccount' => [
            'Card',
            'Letter',
            'Flat',
            'FlatRateEnvelope',
            'FlatRateLegalEnvelope',
            'FlatRatePaddedEnvelope',
            'FlatRateWindowEnvelope',
            'FlatRateCardboardEnvelope',
            'SmallFlatRateEnvelope',
            'Parcel',
            'SoftPack',
            'SmallFlatRateBox',
            'MediumFlatRateBox',
            'LargeFlatRateBox',
            'LargeFlatRateBoxAPOFPO',
            'FlatTubTrayBox',
            'EMMTrayBox',
            'FullTrayBox',
            'HalfTrayBox',
            'PMODSack'
        ],
    ];

    private const ONE_CALL_SHIPMENT_BUY_SERVICES = [
        'DhlEcsAccount' => [
            'DHLBPMExpedited',
            'DHLBPMGround',
            'DHLMarketingParcelGround',
            'DHLMarketingParcelExpedited',
            'DHLPacketPlusInternational',
            'DHLPacketIPA'
        ]
    ];

    private const COMMERCIAL_ADDRESS_ONLY_SERVICES = [
        'FedexAccount' => [
            'FEDEX_GROUND'
        ],
        'FedexDefaultAccount' => [
            'FEDEX_GROUND'
        ],
    ];

    private const ZPL_ONLY_CARRIERS = [
        'UpsMailInnovationsAccount',
        'UpsSurepostAccount'
    ];

    private const SUPPRESS_ETD_ERROR = 'COMMERCIAL_INVOICE is required to process your electronic trade document request';

    private const FORM_TYPE_COMMERCIAL_INVOICE = 'commercial_invoice';

    /**
     * @param EasypostCredential|null $credential
     * @return void
     * @throws ShippingException
     */
    public function getCarriers(ShippingProviderCredential $credential = null): void
    {
        $carrierService = array_search(get_class($this), ShippingComponent::SHIPPING_CARRIERS);
        $credentials = new Collection();

        if (!is_null($credential)) {
            $credentials->add($credential);
        } else {
            $credentials = EasypostCredential::all();
        }

        foreach ($credentials as $credential) {
            $customer = $credential->customer;

            $services = $this->send(
                $credential,
                'GET',
                '/beta/metadata?types=service_levels',
                null,
                true,
                true
            );

            $services = $services['carriers'];

            foreach ($services as $key => $serviceItem) {
                if ($serviceItem['name'] == 'passportglobal') {
                    $services[$key]['human_readable'] = 'Passport Global';
                    $services[$key]['service_levels'] = [
                        ['name' => 'PriorityDdpDelcon'],
                        ['name' => 'PriorityDdp'],
                        ['name' => 'epacketDdp'],
                        ['name' => 'ExpressDdp'],
                        ['name' => 'PriorityDduDelcon'],
                        ['name' => 'PriorityDdu'],
                    ];

                    break;
                }
            }

            try {
                $carriers = $this->send(
                    $credential,
                    'GET',
                    '/carrier_accounts/',
                    null,
                    true,
                    true
                );

                $storedCarrierIds = [];

                foreach ($carriers as $carrier) {
                    if ($this->matchesEasypostReference($credential, $carrier) || $carrier['billing_type'] === self::EASYPOST_BILLING_TYPE) {
                        $shippingCarrier = ShippingCarrier::withTrashed()
                            ->where('customer_id', $customer->id)
                            ->where('carrier_service', $carrierService)
                            ->whereJsonContains('settings', ['external_carrier_id' => Arr::get($carrier, 'id')])
                            ->first();

                        if (!$shippingCarrier) {
                            $shippingCarrier = ShippingCarrier::create([
                                'customer_id' => $customer->id,
                                'carrier_service' => $carrierService,
                                'integration' => self::INTEGRATION_NAME,
                                'settings' => [
                                    'external_carrier_id' => Arr::get($carrier, 'id')
                                ]
                            ]);

                            $shippingCarrier->credential()->associate($credential);
                        }

                        $shippingCarrier->integration = self::INTEGRATION_NAME;
                        $shippingCarrier->name = $shippingCarrierName = Arr::get($carrier, 'readable');

                        if ($carrier['billing_type'] !== self::EASYPOST_BILLING_TYPE) {
                            $shippingCarrier->name = $carrier['description'];
                        }

                        // cannot change it directly because of how laravel loads json column.
                        $settings = $shippingCarrier->settings;
                        $settings['carrier_account_type'] = $carrierAccountType = Arr::get($carrier, 'type');
                        $shippingCarrier->settings = $settings;

                        $shippingCarrier->save();
                        $shippingCarrier->restore();
                        $storedCarrierIds[] = $shippingCarrier->id;

                        $carrierAccountTypeMap = [
                            'UpsDapAccount' => 'ups',
                            'FedexDefaultAccount' => 'fedex',
                            'DhlEcsAccount' => 'dhlecommercesolutions'
                        ];

                        $carrierAccountTypeName = Arr::get($carrierAccountTypeMap, $carrierAccountType);

                        $shippingService = array_filter($services, static function ($value) use ($shippingCarrierName, $carrierAccountTypeName) {
                            return (!empty($carrierAccountTypeName) && $value['name'] == $carrierAccountTypeName) ||
                                $value['human_readable'] === $shippingCarrierName;
                        });
                        $shippingService = array_merge(...$shippingService);

                        $storedMethodIds = [];

                        if ($shippingService) {
                            foreach ($shippingService['service_levels'] as $service) {
                                $shippingMethod = ShippingMethod::withTrashed()->firstOrCreate([
                                    'shipping_carrier_id' => $shippingCarrier->id,
                                    'name' => Arr::get($service, 'name')
                                ]);

                                $shippingMethod->restore();
                                $storedMethodIds[] = $shippingMethod->id;
                            }
                        }

                        $shippingCarrier->shippingMethods()
                            ->withTrashed()
                            ->whereNotIn('id', $storedMethodIds)
                            ->where(function (Builder $query) {
                                $query->whereNull('source')
                                    ->orWhere('source', '!=', ShippingMethod::DYNAMICALLY_ADDED);
                            })
                            ->delete();
                    }
                }

                $shippingCarriersToDelete = $credential->shippingCarriers()
                    ->withTrashed()
                    ->whereNotIn('id', $storedCarrierIds)
                    ->get();

                foreach ($shippingCarriersToDelete as $shippingCarrierToDelete) {
                    $shippingCarrierToDelete->shippingMethods()->withTrashed()->delete();
                    $shippingCarrierToDelete->delete();
                }

            } catch (Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }

    /**
     * @param Order $order
     * @param FormRequest $storeRequest
     * @param ShippingMethod|null $shippingMethod
     * @return array
     * @throws ShippingException|Exception|GuzzleException
     */
    public function ship(Order $order, FormRequest $storeRequest, ShippingMethod $shippingMethod = null): array
    {
        return $this->processShipment($order, $storeRequest, false, $shippingMethod);
    }

    /**
     * @param Order $order
     * @param $storeRequest
     * @return Return_|null
     * @throws ShippingException|GuzzleException
     */
    public function return(Order $order, $storeRequest): ?Return_
    {
        $input = $storeRequest->validated();

        $input['number'] = Return_::getUniqueIdentifier(ReturnComponent::NUMBER_PREFIX, $input['warehouse_id']);

        $shippingRateId = $input['shipping_method_id'];
        $shippingMethod = ShippingMethod::find($shippingRateId);

        $defaultBox = $order->getDefaultShippingBox();

        $packingStateItemsArr = [];
        $totalWeight = 0;

        foreach ($input['order_items'] as $record)
        {
            $orderItem = OrderItem::find($record['order_item_id']);
            for ($quantityIndex = 0; $quantityIndex < $record['quantity']; $quantityIndex++) {
                $packingStateItemsArr[] = [
                    'orderItem' => $record['order_item_id'],
                    'location' => $record['location_id'],
                    'tote' => $record['tote_id'],
                    'serialNumber' => '',
                    'packedParentKey' => ''
                ];
            }
            $totalWeight += $orderItem->weight;
        }

        $packingStateItem = [
            'items' => $packingStateItemsArr,
            'weight' => $totalWeight,
            'box' => $defaultBox->id,
            '_length' => $defaultBox->length,
            'width' => $defaultBox->width,
            'height' => $defaultBox->height,
        ];

        $packageItemRequest = PackageItemRequest::make($packingStateItem);

        $carrierAccountType = Arr::get($shippingMethod->shippingCarrier->settings, 'carrier_account_type');
        $requestedLabelFormat = $labelFormat = customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_USE_ZPL_LABELS) ? 'zpl' : 'pdf';

        // With bulk shipping, we are merging the labels together in one big PDF. We cannot merge ZPLs.
        if (Arr::get($storeRequest, 'bulk_ship_batch_id')) {
            $requestedLabelFormat = 'pdf';
        }

        // Check if carrier only supports ZPL
        if ($requestedLabelFormat === 'pdf' &&
            $carrierAccountType &&
            in_array($carrierAccountType, self::ZPL_ONLY_CARRIERS)
        ) {
            $labelFormat = 'zpl';
        }

        $requestBody = $this->createReturnRequestBody($order, $storeRequest, $packageItemRequest, $shippingMethod, $labelFormat);

        $response = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            '/shipments',
            $requestBody
        );

        if ($response && Arr::get($response, 'rates')) {
            $rates = Arr::get($response, 'rates');

            foreach ($rates as $rate) {
                if ($rate['service'] === $shippingMethod->name && $rate['carrier_account_id'] === $shippingMethod->shippingCarrier->settings['external_carrier_id']) {
                    $rateId = $rate['id'];
                    $shipmentId = $rate['shipment_id'];

                    $carrierResponse = $this->send(
                        $shippingMethod->shippingCarrier->credential,
                        'POST',
                        '/shipments/' . $shipmentId . '/buy',
                        [
                            'rate' => [
                                'id' => $rateId
                            ]
                        ]
                    );

                    $return = app('return')->createReturn($order, $input);

                    $this->storeReturnLabelAndTracking($return, $carrierResponse, $requestedLabelFormat);

                    return $return;
                }
            }
        }

        return null;
    }

    /**
     * @param Order $order
     * @param PackageItemRequest $packageItemRequest
     * @param ShippingMethod $shippingMethod
     * @param string $labelFormat
     * @param bool $suppressEtd
     * @return array
     */
    public function createShipmentRequestBody(Order $order, PackageItemRequest $packageItemRequest, ShippingMethod $shippingMethod, string $labelFormat, bool $suppressEtd = false): array
    {
        $request['shipment']['reference'] = $order->number;

        $packageItemInput = $packageItemRequest->all();
        $customerAddress = $order->customer->contactInformation;
        $customerWarehouseAddress = $order->customer->shipFromContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->shipFromContactInformation;

            if (empty($customerWarehouseAddress)) {
                $warehouse = $order->warehouse;

                if (!$warehouse) {
                    $warehouse = $order->customer->parent_id ? $order->customer->parent->warehouses->first() : $order->customer->warehouses->first();
                }

                $customerWarehouseAddress = $warehouse->contactInformation;
                $senderName = $customerAddress->name;
            } else {
                $senderName = $customerWarehouseAddress->name;
            }
        } else {
            $senderName = $customerWarehouseAddress->name;
        }

        $shippingContactInformation = $order->shippingContactInformation;
        $billingContactInformation = $order->billingContactInformation;

        $request['shipment']['from_address'] = $this->setSenderAddressForRequest($customerWarehouseAddress, $senderName);
        $request['shipment']['to_address'] = $this->setDeliveryAddressForRequest($shippingContactInformation);
        $request['shipment']['buyer_address'] = $this->setDeliveryAddressForRequest($billingContactInformation);

        $parcel['length'] = (string) max(0.01, $packageItemRequest->_length);
        $parcel['width'] = (string) max(0.01, $packageItemRequest->width);
        $parcel['height'] = (string) max(0.01, $packageItemRequest->height);
        $parcel['weight'] = (string) max(0.01, $this->getWeightInOz($order->customer, $packageItemRequest->weight));

        $shippingBox = ShippingBox::find($packageItemRequest->box);

        $carrierAccountType = Arr::get($shippingMethod->shippingCarrier->settings, 'carrier_account_type');

        if ($shippingBox
            && isset(self::CARRIER_PREDEFINED_PACKAGES[$carrierAccountType])
            && in_array($shippingBox->name, self::CARRIER_PREDEFINED_PACKAGES[$carrierAccountType])
        ) {
            $parcel['predefined_package'] = $shippingBox->name;
        }

        $customsInfo = $this->prepareCustomsInfo($packageItemInput['items'], $order);

        $request['shipment']['parcel'] = $parcel;
        $request['shipment']['customs_info'] = $customsInfo;

        $request['shipment']['options'] = [
            'label_size' => '4x6',
            'label_format' => $labelFormat,
            'print_custom_1' => $order->customer->contactInformation->name ?? '',
            'print_custom_2' => $order->number,
            'special_rates_eligibility' => 'USPS.MEDIAMAIL',
        ];

        $deliveryConfirmation = $this->mapDeliveryConfirmation($order);

        if ($deliveryConfirmation) {
            $request['shipment']['options']['delivery_confirmation'] = $deliveryConfirmation;
        }

        $incoterms = $order->incoterms ?? $shippingMethod->incoterms;

        if ($incoterms) {
            $request['shipment']['options']['incoterm'] = $incoterms;
        }

        $commercialInvoiceSignature = $shippingMethod->shippingCarrier->credential->commercial_invoice_signature;

        if ($commercialInvoiceSignature) {
            $request['shipment']['options']['commercial_invoice_signature'] = $commercialInvoiceSignature;
        }

        $commercialInvoiceLetterhead = $shippingMethod->shippingCarrier->credential->commercial_invoice_letterhead;

        if ($commercialInvoiceLetterhead) {
            $request['shipment']['options']['commercial_invoice_letterhead'] = $commercialInvoiceLetterhead;
        }

        $endorsement = $shippingMethod->shippingCarrier->credential->endorsement;

        if ($endorsement) {
            $request['shipment']['options']['endorsement'] = $endorsement;
        }

        if ($suppressEtd) {
            $request['shipment']['options']['suppress_etd'] = true;
            $request['shipment']['options']['commercial_invoice_format'] = 'PNG';
        }

        if ($order->saturday_delivery) {
            $request['shipment']['options']['saturday_delivery'] = true;
        }

        if ($order->handling_instructions) {
            $request['shipment']['options']['handling_instructions'] = $order->handling_instructions;
        }

        $currency = $this->getCurrency($order);
        if ($currency) {
            $request['shipment']['options']['currency'] = $currency;
        }

        $hazmat = $order->hazmat;

        if (!$hazmat) {
            foreach ($order->orderItems as $orderItem) {
                if ($productHazmat = $orderItem->product?->hazmat) {
                    $hazmat = $productHazmat;
                    break;
                }
            }
        }

        if ($hazmat) {
            $request['shipment']['options']['hazmat'] = $hazmat;
        }

        return $request;
    }

    public function createReturnRequestBody(Order $order, $storeRequest, PackageItemRequest $packageItemRequest, ShippingMethod $shippingMethod, $labelFormat)
    {
        $packageItemInput = $packageItemRequest->all();
        $input = $storeRequest->all();

        $customerAddress = $order->customer->contactInformation;
        $carrierAccountType = $shippingMethod->shippingCarrier->settings['carrier_account_type'] ?? null;
        $customerWarehouseAddress = $order->customer->returnToContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->returnToContactInformation;

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

        $shippingContactInformation = $order->shippingContactInformation;
        $billingContactInformation = $order->billingContactInformation;

        $request['shipment']['from_address'] = $this->setSenderAddressForRequest($shippingContactInformation);
        $request['shipment']['to_address'] = $this->setDeliveryAddressForRequest($customerWarehouseAddress, $senderName);
        $request['shipment']['buyer_address'] = $this->setDeliveryAddressForRequest($billingContactInformation);

        $parcel['length'] = (string) max(0.01, $packageItemRequest->_length);
        $parcel['width'] = (string) max(0.01, $packageItemRequest->width);
        $parcel['height'] = (string) max(0.01, $packageItemRequest->height);
        $parcel['weight'] = (string) max(0.01, $this->getWeightInOz($order->customer, $packageItemRequest->weight));

        $request['shipment']['parcel'] = $parcel;
        $request['shipment']['customs_info'] = $this->prepareCustomsInfo($packageItemInput['items'], $order, minValue: 0.01);

        $request['shipment']['options'] = [
            'label_size' => '4x6',
            'label_format' => $labelFormat,
            'print_custom_1' => __('Return #') . $order->number,
        ];

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
            base64_encode($this->getLabelContent($shipment, $carrierResponse, $requestedLabelFormat)),
            Arr::get($carrierResponse, 'postage_label.label_size'),
            Arr::get($carrierResponse, 'postage_label.label_url'),
            ShipmentLabel::TYPE_SHIPPING,
            $requestedLabelFormat
        );

        app('shipping')->storeShipmentTracking(
            $shipment,
            Arr::get($carrierResponse, 'tracker.tracking_code'),
            $this->getTrackingUrl($shipment, $carrierResponse)
        );
    }

    private function storePackageDocuments(Package $package, $carrierResponse): void
    {
        $forms = Arr::get($carrierResponse, 'forms', []);

        foreach ($forms as $form) {
            $documentType = Arr::get($form, 'form_type');

            if ($documentType === self::FORM_TYPE_COMMERCIAL_INVOICE) {
                $url = Arr::get($form, 'form_url');
                $submittedElectronically = (bool)Arr::get($form, 'submitted_electronically');
                // we should print the commercial invoice with the labels if it was not submitted electronically
                // but a decision has been made to not do that yet.
                $printWithLabel = false;

                PackageDocument::create([
                    'package_id' => $package->id,
                    'document_type' => \File::extension($url),
                    'type' => PackageDocument::TYPE_COMMERCIAL_INVOICE,
                    'url' => $url,
                    'submitted_electronically' => $submittedElectronically,
                    'print_with_label' => $printWithLabel
                ]);
            }
        }
    }

    /**
     * @param Shipment $shipment
     * @param $carrierResponse
     * @return string
     */
    private function getTrackingUrl(Shipment $shipment, $carrierResponse): string
    {
        $trackingNumber = Arr::get($carrierResponse, 'tracker.tracking_code');
        $trackingUrl = Arr::get($carrierResponse, 'tracker.public_url');

        if ($shipment->shippingMethod->shippingCarrier->credential->use_native_tracking_urls) {
            $carrierAccountType = Arr::get($shipment->shippingMethod->shippingCarrier->settings, 'carrier_account_type');

            if (Str::startsWith($carrierAccountType, ['Usps'])) {
                $trackingUrl = 'https://tools.usps.com/go/TrackConfirmAction?tRef=fullpage&tLc=2&text28777=&tLabels=' . $trackingNumber;
            } else if (Str::startsWith($carrierAccountType, ['DhlEcs'])) {
                $trackingUrl = 'https://webtrack.dhlecs.com/orders?trackingNumber=' . $trackingNumber;
            } else if (Str::startsWith($carrierAccountType, ['DhlExpress'])) {
                $trackingUrl = 'https://www.dhl.com/us-en/home/tracking/tracking-ecommerce.html?submit=1&tracking-id=' . $trackingNumber;
            } else if (Str::startsWith($carrierAccountType, ['Fedex'])) {
                $trackingUrl = 'https://www.fedex.com/fedextrack/?trknbr=' . $trackingNumber;
            } else if (Str::startsWith($carrierAccountType, ['Ups'])) {
                $trackingUrl = 'https://www.ups.com/track?loc=null&tracknum=' . $trackingNumber .'&requester=MB/trackdetails';
            } else if (Str::startsWith($carrierAccountType, ['Gso'])) {
                $trackingUrl = 'https://www.gls-us.com/track-and-trace?TrackingNumbers=' . $trackingNumber;
            } elseif (Str::startsWith($carrierAccountType, ['Passport'])) {
                $trackingUrl = 'https://track.passportshipping.com/' . $trackingNumber;
            } elseif (Str::startsWith($carrierAccountType, ['Ontrac'])) {
                $trackingUrl = 'https://www.ontrac.com/tracking/?number=' . $trackingNumber;
            }
        }

        return $trackingUrl;
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
            Arr::get($carrierResponse, 'postage_label.label_size'),
            Arr::get($carrierResponse, 'postage_label.label_url'),
            $requestedLabelFormat
        );

        app('return')->storeReturnTracking(
            $return,
            Arr::get($carrierResponse, 'tracker.tracking_code'),
            Arr::get($carrierResponse, 'tracker.public_url')
        );
    }

    /**
     * @throws GuzzleException
     * @throws ShippingException
     */
    private function send(EasypostCredential $easypostCredential, $method, $endpoint, $data = null, $returnException = true, $forceProductionKey = false)
    {
        Log::info('[Easypost] send', [
            'easypost_credential_id' => $easypostCredential->id,
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
        ]);

        $credentials = $this->getApiCredentials($easypostCredential, $forceProductionKey);

        if (Str::contains($endpoint, 'beta')) {
            $url = 'https://api.easypost.com' . $endpoint;
        } else {
            $url = self::BASE_URL . $endpoint;
        }

        try {
            $client = App::make(Client::class);

            Log::debug($url);

            $defaultOptions = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode(Arr::get($credentials, 'apiKey') . ':'),
                ],
            ];

            if ($method === 'GET') {
                $response = $client->request($method, $url, $defaultOptions);
            } else {
                $response = $client->request($method, $url, [...$defaultOptions, 'body' => json_encode($data)]);
            }

            $body = json_decode($response->getBody()->getContents() ?? null, true);

            Log::info('[Easypost] response', [$body]);

            return $body;
        } catch (RequestException $exception) {
            $logLevel = 'error';

            if (\Str::startsWith($exception->getResponse()->getStatusCode(), 4)) {
                $logLevel = 'info';
            }

            Log::log($logLevel, '[Easypost] exception thrown', [
                $exception->getResponse()->getStatusCode(),
                $exception->getResponse()->getBody()
            ]);

            if ($returnException) {
                throw new ShippingException($exception->getResponse()->getBody());
            }
        }

        return null;
    }

    private function getApiCredentials(EasypostCredential $easypostCredential, $forceProductionKey = false): array
    {
        $apiKey = $forceProductionKey ? $easypostCredential->api_key : (empty($easypostCredential->test_api_key) ? $easypostCredential->api_key : $easypostCredential->test_api_key);
        return compact('apiKey');
    }

    private function getWeightInOz(Customer $customer, $weight)
    {
        $weightUnit = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT);

        if ($weightUnit === 'lb') {
            return $weight * 16;
        }

        if ($weightUnit === 'oz') {
            return $weight;
        }

        if ($weightUnit === 'kg') {
            return $weight * 35.274;
        }

        if ($weightUnit === 'g') {
            return $weight * 0.035274;
        }

        return $weight;
    }

    /**
     * @param Model $object
     * @param $carrierResponse
     * @param $requestedFormat
     * @return string
     */
    private function getLabelContent(Model $object, $carrierResponse, $requestedFormat): string
    {
        try {
            $carrierAccountType = Arr::get($object->shippingMethod->shippingCarrier->settings, 'carrier_account_type');
            $shippingMethodName = $object->shippingMethod->name;
            $documentType = strtolower(Arr::get($carrierResponse, 'options.label_format'));
            $labelUrl = Arr::get($carrierResponse, 'postage_label.label_url');

            $labelWidth = paper_width($object->order->customer_id, 'label');
            $labelHeight = paper_height($object->order->customer_id, 'label');

            $labelContent = file_get_contents($labelUrl);

            // only convert when user wants PDFs and not ZPLs
            if ($documentType === 'zpl' && $requestedFormat !== $documentType) {
                $zplContent = file_get_contents($labelUrl);
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
                $adjustOrientation = false;

                if (Str::startsWith($carrierAccountType, ['DhlExpress'])) {
                    $size = $fpdi->getTemplateSize($tplId, null, $labelHeight);
                    $size['x'] = -10;
                } else if (Str::startsWith($carrierAccountType, ['Fedex'])) {
                    $size = $fpdi->getTemplateSize($tplId, $labelWidth * 1.85);
                    $size['x'] = -5;
                    $adjustOrientation = true;
                } else if (Str::startsWith($carrierAccountType, ['Ups']) && $shippingMethodName == 'WorldwideEconomyDDP') {
                    $size = $fpdi->getTemplateSize($tplId, $labelWidth * 1.4);
                    $size['x'] = -90;
                    $size['y'] = 55;
                    $fpdi->Rotate(-90, $size['width'] / 2, $size['height'] / 2);
                } else {
                    $size = $fpdi->getTemplateSize($tplId);
                    $adjustOrientation = true;
                }

                if ($adjustOrientation) {
                    $orientation = Arr::get($size, 'orientation');

                    if ($orientation) {
                        $fpdi->setPageOrientation($orientation);
                    }
                }

                $fpdi->useTemplate($tplId, $size);

            }

            return $fpdi->Output('label.pdf', 'S');
        } catch (Exception $exception) {
            Log::error('[Easypost] getLabelContent', [$exception->getMessage()]);
            return '';
        }
    }

    /**
     * @param Shipment $shipment
     * @return array
     * @throws GuzzleException
     * @throws ShippingException
     */
    public function void(Shipment $shipment): array
    {
        $response = $this->send(
            $shipment->shippingMethod->shippingCarrier->credential,
            'POST',
            '/shipments/' . $shipment->external_shipment_id . '/refund'
        );

        if (!empty($response)) {
            $shipment->voided_at = Carbon::now();

            $shipment->saveQuietly();

            return ['success' => true, 'message' => __('Shipment successfully voided.')];
        }

        return ['success' => false, 'message' => __('Something went wrong!')];
    }

    /**
     * @param mixed $packingStateItem
     * @param Order $order
     * @param ShippingMethod $shippingMethod
     * @param Shipment $shipment
     * @param string $labelFormat
     * @param string $requestedLabelFormat
     * @return void
     * @throws GuzzleException
     * @throws ShippingException
     */
    private function createAutoReturnLabels(mixed $packingStateItem, Order $order, ShippingMethod $shippingMethod, Shipment $shipment, string $labelFormat, string $requestedLabelFormat): void
    {
        $packageItemRequest = PackageItemRequest::make($packingStateItem);

        $shipmentRequestBody = $this->createShipmentRequestBody($order,
            $packageItemRequest,
            $shippingMethod,
            $labelFormat
        );

        $autoReturnLabelRequestBody = $this->createAutoReturnLabelRequestBody($shipmentRequestBody, $order);

        $carrierResponse = $this->send(
            $shippingMethod->shippingCarrier->credential,
            'POST',
            '/shipments',
            $autoReturnLabelRequestBody,
            false
        );

        if ($carrierResponse && Arr::get($carrierResponse, 'rates')) {
            $rates = Arr::get($carrierResponse, 'rates');

            foreach ($rates as $rate) {
                if ($rate['service'] === $shippingMethod->name && $rate['carrier_account_id'] === $shippingMethod->shippingCarrier->settings['external_carrier_id']) {
                    $rateId = $rate['id'];
                    $shipmentId = $rate['shipment_id'];

                    $response = $this->send(
                        $shippingMethod->shippingCarrier->credential,
                        'POST',
                        '/shipments/' . $shipmentId . '/buy',
                        [
                            'rate' => [
                                'id' => $rateId
                            ]
                        ]
                    );

                    app('shipping')->storeShipmentLabel(
                        $shipment,
                        base64_encode($this->getLabelContent($shipment, $response, $requestedLabelFormat)),
                        Arr::get($response, 'postage_label.label_size'),
                        Arr::get($response, 'postage_label.label_url'),
                        ShipmentLabel::TYPE_RETURN,
                        $requestedLabelFormat
                    );

                    app('shipping')->storeShipmentTracking(
                        $shipment,
                        Arr::get($response, 'tracker.tracking_code'),
                        Arr::get($response, 'tracker.public_url'),
                        ShipmentTracking::TYPE_RETURN
                    );
                }
            }
        }
    }

    /**
     * @param array $shipmentRequestBody
     * @return array
     */
    private function createAutoReturnLabelRequestBody(array $shipmentRequestBody, Order $order): array
    {
        $customerWarehouseAddress = $order->customer->returnToContactInformation;

        if (empty($customerWarehouseAddress)) {
            $customerWarehouseAddress = $order->customer->parent?->returnToContactInformation;
        }

        if (!empty($customerWarehouseAddress)) {
            $customerAddress = $order->customer->contactInformation;
            $senderAddress = $this->setDeliveryAddressForRequest($customerWarehouseAddress, $customerAddress->name);
        } else {
            $senderAddress = $shipmentRequestBody['shipment']['from_address'];
        }

        $deliveryAddress = $shipmentRequestBody['shipment']['to_address'];

        $shipmentRequestBody['shipment']['from_address'] = $deliveryAddress;
        $shipmentRequestBody['shipment']['to_address'] = $senderAddress;

        return $shipmentRequestBody;
    }

    private function getContentsSigner(Customer $customer)
    {
        if ($contentsSigner = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_SIGNER)) {
            return $contentsSigner;
        }

        if ($customer->parent_id) {
            if ($contentsSigner = customer_settings($customer->parent_id, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_SIGNER)) {
                return $contentsSigner;
            }
        }
    }

    private function getContentsType(Customer $customer)
    {
        if ($contentsType = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_CONTENTS_TYPE)) {
            return $contentsType;
        }

        if ($customer->parent_id) {
            if ($contentsType = customer_settings($customer->parent_id, CustomerSetting::CUSTOMER_SETTING_CONTENTS_TYPE)) {
                return $contentsType;
            }
        }
    }

    private function getContentsExplanation(Customer $customer)
    {
        if ($ContentsExplanation = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_DESCRIPTION)) {
            return $ContentsExplanation;
        }

        if ($customer->parent_id) {
            if ($ContentsExplanation = customer_settings($customer->parent_id, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_DESCRIPTION)) {
                return $ContentsExplanation;
            }
        }
    }

    private function getEelPfc(Customer $customer)
    {
        if ($eelPfc = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_EEL_PFC)) {
            return $eelPfc;
        }

        if ($customer->parent_id) {
            if ($eelPfc = customer_settings($customer->parent_id, CustomerSetting::CUSTOMER_SETTING_EEL_PFC)) {
                return $eelPfc;
            }
        }
    }

    public function manifest(ShippingCarrier $shippingCarrier)
    {
        $count = 100;

        if (config('app.env') === 'production') {
            $count = config('easypost.manifest_batch_size');

            if (!Str::startsWith(Arr::get($shippingCarrier->settings, 'carrier_account_type'), ['DhlEcs'])) {
                return;
            }
        }

        Shipment::whereIn('shipping_method_id', $shippingCarrier->shippingMethods->pluck('id'))
            ->whereNull('voided_at')
            ->whereNull('external_manifest_id')
            ->chunkById(/**
             * @param Shipment[] $shipments
             * @return void
             */ $count, function($shipments) use ($shippingCarrier) {
                $batchRequest = [
                    'batch' => [
                        'shipments' => []
                    ]
                ];

                foreach ($shipments as $shipment) {
                    $batchRequest['batch']['shipments'][] = [
                        'id' => $shipment->external_shipment_id
                    ];
                }

                $batchResponse = $this->send($shippingCarrier->credential,
                    'POST',
                    '/batches',
                    $batchRequest
                );

                if ($batchResponse && Arr::get($batchResponse, 'id')) {
                    Shipment::whereIntegerInRaw('id', $shipments->pluck('id')->toArray())->update([
                        'external_manifest_id' => Arr::get($batchResponse, 'id')
                    ]);
                }
            });
    }

    public function scanformBatches(ShippingCarrier $shippingCarrier)
    {
        $shipments = Shipment::whereIn('shipping_method_id', $shippingCarrier->shippingMethods->pluck('id'))
            ->whereNotNull('external_manifest_id')
            ->where('external_manifest_id', '!=', 'ignore')
            ->whereDate('created_at', '>', now()->subDays(7)->toDateString())
            ->groupBy('external_manifest_id')
            ->get();

        foreach ($shipments as $shipment) {
            $batch = $this->getBatch($shippingCarrier->credential, $shipment->external_manifest_id);

            if (!empty($batch) && empty($batch->scan_form)) {
                $this->send($shippingCarrier->credential,
                    'POST',
                    '/batches/' . $shipment->external_manifest_id . '/scan_form'
                );
            }
        }
    }

    /**
     * @throws ShippingException|GuzzleException
     */
    public function getBatch(EasypostCredential $easypostCredential, $batchId)
    {
        return $this->send($easypostCredential,
            'GET',
            '/batches/' . $batchId
        );
    }

    /**
     * @throws ShippingException|GuzzleException
     */
    public function getCarrierTypes(EasypostCredential $credential)
    {
        return $this->send(
            $credential,
            'GET',
            '/carrier_types',
            null,
            true,
            true
        );
    }

    /**
     * @param EasypostCredential $credential
     * @param string $carrierAccountId
     * @return mixed|null
     * @throws ShippingException|GuzzleException
     */
    public function getCarrierAccount(EasypostCredential $credential, string $carrierAccountId): mixed
    {
        return $this->send(
            $credential,
            'GET',
            '/carrier_accounts/' . $carrierAccountId,
            null,
            true,
            true
        );
    }

    /**
     * @param EasypostCredential $credential
     * @return array
     * @throws ShippingException|GuzzleException
     */
    public function getCarrierAccounts(EasypostCredential $credential): array
    {
        $carriers = $this->send(
            $credential,
            'GET',
            '/carrier_accounts/',
            null,
            true,
            true
        );

        $carrierAccounts = [];

        foreach ($carriers as $carrier) {
            if ($this->matchesEasypostReference($credential, $carrier) && $carrier['billing_type'] !== self::EASYPOST_BILLING_TYPE) {
                $carrierAccount = ShippingCarrier::where('customer_id', $credential->customer_id)
                    ->whereJsonContains('settings', ['external_carrier_id' => $carrier['id']])
                    ->first();

                if ($carrierAccount) {
                    $carrierAccounts[] = $carrierAccount;
                }
            }
        }

        return $carrierAccounts;
    }

    /**
     * @param EasypostCredential $credential
     * @param CreateAccountRequest $request
     * @return void
     * @throws ShippingException|GuzzleException
     */
    public function createCarrierAccount(EasypostCredential $credential, CreateAccountRequest $request): void
    {
        $data = $request->validated();

        $data['reference'] = $this->generateEasypostReference($credential, $data['type']);

        $data = $this->filterCarrierAccountRequest($data);

        $this->send(
            $credential,
            'POST',
            '/carrier_accounts',
            $data,
            true,
            true
        );

        $this->getCarriers($credential);
    }

    /**
     * @param EasypostCredential $credential
     * @param UpdateAccountRequest $request
     * @return void
     * @throws ShippingException
     * @throws GuzzleException
     */
    public function updateCarrierAccount(EasypostCredential $credential, UpdateAccountRequest $request): void
    {
        $data = $request->validated();

        $data = $this->filterCarrierAccountRequest($data);

        $this->send(
            $credential,
            'PATCH',
            '/carrier_accounts/' . $data['carrier_account_id'],
            $data,
            true,
            true
        );
    }

    /**
     * @param EasypostCredential $credential
     * @param DeleteAccountRequest $request
     * @return void
     * @throws ShippingException|GuzzleException
     */
    public function deleteCarrierAccount(EasypostCredential $credential, DeleteAccountRequest $request): void
    {
        $data = $request->validated();

        $this->send(
            $credential,
            'DELETE',
            '/carrier_accounts/' . $data['carrier_account_id'],
            null,
            true,
            true
        );

        $carrier = ShippingCarrier::where('customer_id', $data['customer_id'])
            ->whereJsonContains('settings', ['external_carrier_id' => $data['carrier_account_id']])
            ->first();

        foreach ($carrier->shippingMethods as $shippingMethod) {
            $shippingMethod->shippingMethodMappings()->forceDelete();

            $shippingMethod->returnShippingMethodMappings()->forceDelete();
        }

        $carrier->shippingMethods()->delete();

        $carrier->delete();
    }

    /**
     * @param EasypostCredential $credential
     * @param mixed $type
     * @return string
     */
    private function generateEasypostReference(EasypostCredential $credential, mixed $type): string
    {
        if ($credential->reference_prefix) {
            $referencePrefix = $credential->reference_prefix;
        } else {
            $referencePrefix = Str::slug(Arr::get($_SERVER, 'SERVER_NAME', config('app.url')) . ' ' . $credential->customer->contactInformation->name);
        }

        return Str::slug($referencePrefix . ' ' . $type);
    }

    /**
     * Check if a carrier account is configured for this Packiyo instance and this customer.
     *
     * @param EasypostCredential $credential
     * @param array $carrier
     * @return bool
     */
    public function matchesEasypostReference(EasypostCredential $credential, array $carrier): bool
    {
        if (!Str::contains($carrier['reference'] ?? '', ['localhost', 'packiyo'])) {
            return true;
        }

        if ($credential->reference_prefix) {
            $referencePrefix = $credential->reference_prefix;
        } else {
            $referencePrefix = Str::slug(Arr::get($_SERVER, 'SERVER_NAME', config('app.url')) . ' ' . $credential->customer->contactInformation->name);
        }

        return empty($carrier['reference']) || Str::startsWith($carrier['reference'], $referencePrefix);
    }

    /**
     * @param array $data
     * @return array
     */
    private function filterCarrierAccountRequest(array $data): array
    {
        Arr::forget($data, ['customer_id', 'easypost_credential_id']);

        $data['credentials'] = array_filter($data['credentials']);

        if (empty($data['credentials'])) {
            Arr::forget($data, 'credentials');
        }

        if (isset($data['test_credentials'])) {
            $data['test_credentials'] = array_filter($data['test_credentials']);

            if (empty($data['test_credentials'])) {
                Arr::forget($data, 'test_credentials');
            }
        }

        return $data;
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     * @throws GuzzleException
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
            $responses = $this->fetchRates($params['credentials'], $shipmentData, $order);
            $customerIds = [$order->customer_id];

            if ($order->customer->parent_id) {
                $customerIds[] = $order->customer->parent_id;
            }

            $rates = [];

            foreach ($responses as $response) {
                $carriers = [];

                if (empty($response['rates']) && !empty($response['messages'])) {
                    $messages = [];

                    foreach ($response['messages'] as $message) {
                        $carrierAccountId = Arr::get($message, 'carrier_account_id');

                        if ($carrierAccountId) {
                            $shippingCarrier = Arr::get($carriers, $carrierAccountId);

                            if (!$shippingCarrier) {
                                $shippingCarrier = ShippingCarrier::whereIn('customer_id', $customerIds)
                                    ->whereJsonContains('settings', ['external_carrier_id' => $carrierAccountId])
                                    ->first();
                            }

                            if ($shippingCarrier) {
                                $carriers[$carrierAccountId] = $shippingCarrier;

                                $messages[$shippingCarrier->getNameAndIntegrationAttribute()]['errors'][] = [
                                    'error_type' => $message['type'],
                                    'message' => $message['message']
                                ];
                            }
                        }
                    }

                    return $messages;
                }

                foreach ($response['rates'] as $rate) {
                    $carrierAccountId = Arr::get($rate, 'carrier_account_id');

                    $shippingCarrier = Arr::get($carriers, $carrierAccountId);

                    if (!$shippingCarrier) {
                        $shippingCarrier = ShippingCarrier::whereIn('customer_id', $customerIds)
                            ->whereJsonContains('settings', ['external_carrier_id' => $carrierAccountId])
                            ->first();
                    }

                    if ($shippingCarrier) {
                        $carriers[$carrierAccountId] = $shippingCarrier;

                        $carrierNameAndIntegration = $shippingCarrier->getNameAndIntegrationAttribute();

                        $shippingMethod = $shippingCarrier->shippingMethods()
                            ->where('name', $rate['service'])
                            ->first();

                        if ($shippingMethod) {
                            $rates[$carrierNameAndIntegration][] = [
                                'service' => $rate['service'],
                                'rate' => $rate['rate'],
                                'currency' => $rate['currency'],
                                'delivery_days' => $rate['delivery_days'],
                                'shipping_method_id' => $shippingMethod->id
                            ];
                        } else {
                            $newShippingMethod = new ShippingMethod([
                                'customer_id' => $shippingCarrier->customer_id,
                                'shipping_carrier_id' => $shippingCarrier->id,
                                'settings' => ['external_carrier_id' => $carrierAccountId],
                                'name' => $rate['service'],
                                'source' => ShippingMethod::DYNAMICALLY_ADDED
                            ]);

                            $newShippingMethod->save();

                            $rates[$carrierNameAndIntegration][] = [
                                'service' => $rate['service'],
                                'rate' => $rate['rate'],
                                'currency' => $rate['currency'],
                                'delivery_days' => $rate['delivery_days'],
                                'shipping_method_id' => $newShippingMethod->id
                            ];
                        }
                    }
                }
            }

            return $rates;
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
        }

        return [];
    }

    /**
     * @param ContactInformation $contactInformation
     * @param string|null $name
     * @return array
     */
    private function setDeliveryAddressForRequest(ContactInformation $contactInformation, string $name = null): array
    {
        $deliveryAddress = [];

        if (!$name) {
            $name = $contactInformation->name;
        }

        $deliveryAddress['name'] = $name;
        $deliveryAddress['company'] = $contactInformation->company_name;
        $deliveryAddress['street1'] = $contactInformation->address;
        $deliveryAddress['street2'] = $contactInformation->address2;
        $deliveryAddress['city'] = $contactInformation->city;
        $deliveryAddress['state'] = $contactInformation->state;
        $deliveryAddress['zip'] = $contactInformation->zip;
        $deliveryAddress['country'] = $contactInformation->country->iso_3166_2 ?? null;
        $deliveryAddress['phone'] = $contactInformation->phone;
        $deliveryAddress['email'] = $contactInformation->email;

        if ($deliveryAddress['country'] == 'US') {
            $deliveryAddress['verify'] = ['delivery'];
        }

        if ($contactInformation->company_number) {
            $deliveryAddress['federal_tax_id'] = $contactInformation->company_number;
        }

        return $deliveryAddress;
    }

    /**
     * @param ContactInformation $contactInformation
     * @param string|null $name
     * @return array
     */
    private function setSenderAddressForRequest(ContactInformation $contactInformation, string $name = null): array
    {
        $senderAddress = [];

        if (!$name) {
            $name = $contactInformation->name;
        }

        $senderAddress['name'] = $name;
        $senderAddress['company'] = $name;
        $senderAddress['street1'] = $contactInformation->address;
        $senderAddress['street2'] = $contactInformation->address2;
        $senderAddress['city'] = $contactInformation->city;
        $senderAddress['state'] = $contactInformation->state;
        $senderAddress['zip'] = $contactInformation->zip;
        $senderAddress['country'] = $contactInformation->country->iso_3166_2 ?? null;
        $senderAddress['phone'] = $contactInformation->phone;
        $senderAddress['email'] = $contactInformation->email;

        if ($contactInformation->company_number) {
            $senderAddress['federal_tax_id'] = $contactInformation->company_number;
        }

        return $senderAddress;
    }

    /**
     * @param Order $order
     * @return string|null
     */
    private function mapDeliveryConfirmation(Order $order): ?string
    {
        return match ($order->delivery_confirmation) {
            Order::DELIVERY_CONFIRMATION_SIGNATURE => 'SIGNATURE',
            Order::DELIVERY_CONFIRMATION_ADULT_SIGNATURE => 'ADULT_SIGNATURE',
            Order::DELIVERY_CONFIRMATION_NO_SIGNATURE => 'NO_SIGNATURE',
            default => null,
        };
    }

    /**
     * @param $shipmentResponse
     * @param Order $order
     * @param $shippingMethod
     * @param $input
     * @param $orderItemsToShip
     * @param $packageItemRequest
     * @param $packingStateItem
     * @param $storeRequest
     * @param $requestedLabelFormat
     * @param $labelFormat
     * @return Shipment|null
     * @throws GuzzleException
     * @throws ShippingException
     */
    public function createShipment($shipmentResponse,
                                   Order $order,
                                   $shippingMethod,
                                   $input,
                                   $orderItemsToShip,
                                   $packageItemRequest,
                                   $packingStateItem,
                                   $storeRequest,
                                   $requestedLabelFormat,
                                   $labelFormat): ?Shipment
    {
        $shipment = app('shipping')->createShipment($order, $shippingMethod, $input, Arr::get($shipmentResponse, 'selected_rate.rate', 0), $shipmentResponse['id']);

        app('shipment')->createContactInformation($order->shippingContactInformation->toArray(), $shipment);

        foreach ($orderItemsToShip as $orderItemToShip) {
            app('shipment')->shipItem($orderItemToShip['shipRequest'], $orderItemToShip['orderItem'], $shipment);
        }

        if ($order->shipments->count() === 1) {
            app('shipment')->shipVirtualProducts($order, $shipment);
        }

        $package = app('shipping')->createPackage($order, $packageItemRequest, $shipment);

        $this->storePackageDocuments($package, $shipmentResponse);

        $this->storeShipmentLabelAndTracking($shipment, $shipmentResponse, $requestedLabelFormat);

        if (customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_AUTO_RETURN_LABEL) === '1') {
            $shippingMethod = app('shippingMethodMapping')->returnShippingMethod($order) ?? $shippingMethod;

            $this->createAutoReturnLabels($packingStateItem, $order, $shippingMethod, $shipment, $labelFormat, $requestedLabelFormat);
        }

        return $shipment;
    }

    /**
     * @param Order $order
     * @param FormRequest $storeRequest
     * @param bool $suppressEtd
     * @param ShippingMethod|null $shippingMethod
     * @return array
     * @throws ShippingException|GuzzleException
     */
    private function processShipment(Order $order, FormRequest $storeRequest, bool $suppressEtd = false, ShippingMethod $shippingMethod = null): array
    {
        $input = $storeRequest->all();

        if (is_null($shippingMethod)) {
            if (empty($input['shipping_method_id'])) {
                $shippingMethod = $order->shippingMethod;
            } else {
                $shippingMethod = ShippingMethod::find($input['shipping_method_id']);
            }
        }

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

            $carrierAccountType = Arr::get($shippingMethod->shippingCarrier->settings, 'carrier_account_type');
            $requestedLabelFormat = $labelFormat = customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_USE_ZPL_LABELS) ? 'zpl' : 'pdf';

            // With bulk shipping, we are merging the labels together in one big PDF. We cannot merge ZPLs.
            if (Arr::get($storeRequest, 'bulk_ship_batch_id')) {
                $requestedLabelFormat = 'pdf';
            }

            // Check if carrier only supports ZPL
            if ($requestedLabelFormat === 'pdf' &&
                $carrierAccountType &&
                in_array($carrierAccountType, self::ZPL_ONLY_CARRIERS)
            ) {
                $labelFormat = 'zpl';
            }

            $shipmentRequestBody = $this->createShipmentRequestBody($order,
                $packageItemRequest,
                $shippingMethod,
                $labelFormat,
                $suppressEtd
            );

            $carrierAccountType = $shippingMethod->shippingCarrier->settings['carrier_account_type'] ?? null;

            if ($carrierAccountType &&
                array_key_exists($carrierAccountType, self::ONE_CALL_SHIPMENT_BUY_SERVICES) &&
                in_array($shippingMethod->name, self::ONE_CALL_SHIPMENT_BUY_SERVICES[$carrierAccountType])
            ) {
                $shipmentRequestBody['shipment']['service'] = $shippingMethod->name;
                $shipmentRequestBody['shipment']['carrier_accounts'] = [$shippingMethod->shippingCarrier->settings['external_carrier_id']];

                $response = $this->send(
                    $shippingMethod->shippingCarrier->credential,
                    'POST',
                    '/shipments',
                    $shipmentRequestBody
                );

                $shipments[] = $this->createShipment($response,
                    $order,
                    $shippingMethod,
                    $input,
                    $orderItemsToShip,
                    $packageItemRequest,
                    $packingStateItem,
                    $storeRequest,
                    $requestedLabelFormat,
                    $labelFormat
                );
            } else {
                $response = $this->send(
                    $shippingMethod->shippingCarrier->credential,
                    'POST',
                    '/shipments',
                    $shipmentRequestBody
                );

                $rates = Arr::get($response, 'rates');

                if ($rates) {
                    foreach ($rates as $rate) {
                        if ($rate['service'] === $shippingMethod->name && $rate['carrier_account_id'] === $shippingMethod->shippingCarrier->settings['external_carrier_id']) {
                            $residentialAddress = Arr::get($response, 'to_address.residential');

                            if ($residentialAddress && array_key_exists($carrierAccountType, self::COMMERCIAL_ADDRESS_ONLY_SERVICES) && in_array($shippingMethod->name, self::COMMERCIAL_ADDRESS_ONLY_SERVICES[$carrierAccountType])) {
                                throw new HttpException(
                                    500,
                                    __('Cannot ship using :method to residential address', ['method' => $shippingMethod->name])
                                );
                            }

                            $rateId = $rate['id'];
                            $shipmentId = $rate['shipment_id'];

                            try {
                                $response = $this->send(
                                    $shippingMethod->shippingCarrier->credential,
                                    'POST',
                                    '/shipments/' . $shipmentId . '/buy',
                                    [
                                        'rate' => [
                                            'id' => $rateId
                                        ]
                                    ]
                                );
                            } catch (Exception $exception) {
                                if ($this->responseHasEtdError($exception->getMessage())
                                    && !$suppressEtd
                                    && !request()->is('bulk_shipping/*')
                                ) {
                                    return $this->processShipment($order, $storeRequest, true);
                                }

                                throw $exception;
                            }

                            $shipments[] = $this->createShipment(
                                $response,
                                $order,
                                $shippingMethod,
                                $input,
                                $orderItemsToShip,
                                $packageItemRequest,
                                $packingStateItem,
                                $storeRequest,
                                $requestedLabelFormat,
                                $labelFormat
                            );

                            break;
                        }
                    }
                }

                if (!$shipments && isset($rates)) {
                    if ($response['messages']) {
                        foreach ($response['messages'] as $ratingError) {
                            if ($ratingError['type'] === 'rate_error') {
                                if (isset($ratingError['carrier_account_id']) && $ratingError['carrier_account_id'] === $shippingMethod->shippingCarrier->settings['external_carrier_id']) {
                                    $customErrorMessage = $ratingError['message'];
                                } elseif (isset($shippingMethod->shippingCarrier->settings['carrier_account_type']) && str_contains($shippingMethod->shippingCarrier->settings['carrier_account_type'], $ratingError['carrier'])) {
                                    $customErrorMessage = $ratingError['message'];
                                }
                            }
                        }
                    }

                    if (empty($rates)) {
                        if (isset($customErrorMessage)) {
                            throw new HttpException(500, $customErrorMessage);
                        }

                        throw new HttpException(500, __('No shipping methods are available'));
                    }

                    throw new HttpException(500, __('Only the following methods are available: :methods. :errorMessage', [
                        'methods' => collect($rates)->map(function($rate) { return $rate['carrier'] . ' ' . $rate['service']; })->join(', '),
                        'errorMessage' => $customErrorMessage ?? ''
                    ]));
                }
            }
        }

        return $shipments;
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     * @throws GuzzleException
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

        $cheapestRates = [];

        foreach (ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS as $key => $cheapestShippingMethod) {
            $cheapestRates[$key] = [];
        }

        try {
            $responses = $this->fetchRates($params['credentials'], $shipmentData, $order);
            $customerIds = [$order->customer_id];

            if ($order->customer->parent_id) {
                $customerIds[] = $order->customer->parent_id;
            }

            $rates = [];

            foreach ($responses as $response) {
                $carriers = [];

                foreach ($response['rates'] as $rate) {
                    $carrierAccountId = Arr::get($rate, 'carrier_account_id');

                    $shippingCarrier = Arr::get($carriers, $carrierAccountId);

                    if (!$shippingCarrier) {
                        $shippingCarrier = ShippingCarrier::whereIn('customer_id', $customerIds)
                            ->whereJsonContains('settings', ['external_carrier_id' => $carrierAccountId])
                            ->first();
                    }

                    if ($shippingCarrier) {
                        $carriers[$carrierAccountId] = $shippingCarrier;

                        $carrierNameAndIntegration = $shippingCarrier->getNameAndIntegrationAttribute();

                        $shippingMethod = $shippingCarrier->shippingMethods()
                            ->where('name', $rate['service'])
                            ->first();

                        if ($shippingMethod) {
                            $rates[$carrierNameAndIntegration][] = [
                                'service' => $rate['service'],
                                'rate' => $rate['rate'],
                                'currency' => $rate['currency'],
                                'delivery_days' => $rate['delivery_days'],
                                'shipping_method_id' => $shippingMethod->id
                            ];
                        }
                    }
                }
            }

            foreach ($rates as $carrier => $rate) {
                foreach ($rate as $service) {
                    if (isset($service['service']) &&
                        (empty($cheapestRates['cheapest-1day']) || $cheapestRates['cheapest-1day']['rate'] > $service['rate']) &&
                        $service['delivery_days'] === 1
                    ) {
                        $cheapestRates['cheapest-1day'] = [
                            'carrier' => $carrier,
                            'service' => $service['service'],
                            'rate' => $service['rate'],
                            'currency' => $service['currency'],
                            'delivery_days' => $service['delivery_days'],
                            'shipping_method_id' => $service['shipping_method_id']
                        ];
                    }

                    if (isset($service['service']) &&
                        (empty($cheapestRates['cheapest-2days']) || $cheapestRates['cheapest-2days']['rate'] > $service['rate']) &&
                        $service['delivery_days'] === 2
                    ) {
                        $cheapestRates['cheapest-2days'] = [
                            'carrier' => $carrier,
                            'service' => $service['service'],
                            'rate' => $service['rate'],
                            'currency' => $service['currency'],
                            'delivery_days' => $service['delivery_days'],
                            'shipping_method_id' => $service['shipping_method_id']
                        ];
                    }

                    if (isset($service['service']) &&
                        (empty($cheapestRates['cheapest-1-3days']) || $cheapestRates['cheapest-1-3days']['rate'] > $service['rate']) &&
                        $service['delivery_days'] >= 1 && $service['delivery_days'] <= 3
                    ) {
                        $cheapestRates['cheapest-1-3days'] = [
                            'carrier' => $carrier,
                            'service' => $service['service'],
                            'rate' => $service['rate'],
                            'currency' => $service['currency'],
                            'delivery_days' => $service['delivery_days'],
                            'shipping_method_id' => $service['shipping_method_id']
                        ];
                    }

                    if (isset($service['service']) &&
                        (empty($cheapestRates['cheapest-3-5days']) || $cheapestRates['cheapest-3-5days']['rate'] > $service['rate']) &&
                        $service['delivery_days'] >= 3 &&
                        $service['delivery_days'] <= 5
                    ) {
                        $cheapestRates['cheapest-3-5days'] = [
                            'carrier' => $carrier,
                            'service' => $service['service'],
                            'rate' => $service['rate'],
                            'currency' => $service['currency'],
                            'delivery_days' => $service['delivery_days'],
                            'shipping_method_id' => $service['shipping_method_id']
                        ];
                    }

                    if (isset($service['service']) &&
                        (empty($cheapestRates['cheapest']) || $cheapestRates['cheapest']['rate'] > $service['rate'])
                    ) {
                        $cheapestRates['cheapest'] = [
                            'carrier' => $carrier,
                            'service' => $service['service'],
                            'rate' => $service['rate'],
                            'currency' => $service['currency'],
                            'delivery_days' => $service['delivery_days'],
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


    private function responseHasEtdError(string $exceptionMessage): bool
    {
        $shippingError = json_decode($exceptionMessage, true);
        $errorMessage = Arr::get($shippingError, 'error.message');

        // temp fix as EP says the message should be string but we started getting arrays.
        if (is_array($errorMessage)) {
            $errorMessage = $errorMessage[0];
        }

        return Str::contains($errorMessage, self::SUPPRESS_ETD_ERROR);
    }

    private function setResidentialFlag(EasypostCredential $easypostCredential, &$address): void
    {
        $easypostAddress = $this->send(
            $easypostCredential,
            'POST',
            '/addresses',
            $address,
            false
        );

        if ($easypostAddress) {
            $address['residential'] = Arr::get($easypostAddress, 'residential');
        }
    }

    public function getCurrency(Order $order): mixed
    {
        if ($order->currency) {
            $currency = $order->currency->code;
        } else {
            $customerCurrency = Currency::find(customer_settings($order->customer->id,
                CustomerSetting::CUSTOMER_SETTING_CURRENCY));

            if ($customerCurrency) {
                $currency = $customerCurrency->code;
            }
        }
        return $currency;
    }

    private function prepareShipmentData(Order $order, array $input, array $package): array
    {
        $shipmentData = [];

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

        $senderAddress = $this->setSenderAddressForRequest($customerWarehouseAddress, $senderName);
        $deliveryAddress = $this->setDeliveryAddressForRequest($order->shippingContactInformation);

        $parcel = $this->prepareParcel($order, $package);

        $shipmentData['shipment']['to_address'] = $deliveryAddress;
        $shipmentData['shipment']['from_address'] = $senderAddress;
        $shipmentData['shipment']['parcel'] = $parcel;

        $incoterms = $order->incoterms;
        if ($incoterms) {
            $shipmentData['shipment']['options']['incoterm'] = $incoterms;
        }

        $shipmentData['shipment']['customs_info'] = $this->prepareCustomsInfo($package['items'], $order);

        return $shipmentData;
    }

    private function prepareParcel(Order $order, array $package): array
    {
        $parcel = [];
        if (empty($package)) {
            $shippingBox = $order->getDefaultShippingBox();

            $parcel['length'] = $shippingBox->length;
            $parcel['width'] = $shippingBox->width;
            $parcel['height'] = $shippingBox->height;
            $parcel['weight'] = 0.01;

            foreach ($order->orderItems as $item) {
                $parcel['weight'] += $this->getWeightInOz($order->customer, $item->weight * $item->quantity_allocated_pickable);
            }
        } else {
            $parcel['length'] = $package['_length'];
            $parcel['width'] = $package['width'];
            $parcel['height'] = $package['height'];
            $parcel['weight'] = max(0.01, $this->getWeightInOz($order->customer, $package['weight']));
        }

        if (!isset($shippingBox)) {
            $shippingBox = ShippingBox::find($package['box']);

            if ($shippingBox) {
                foreach (self::CARRIER_PREDEFINED_PACKAGES as $carrierPackage) {
                    if (in_array($shippingBox->name, $carrierPackage)) {
                        $parcel['predefined_package'] = $shippingBox->name;
                    }
                }
            }
        }

        return $parcel;
    }

    public function prepareCustomsInfo($items, Order $order, float $minValue = 1): array
    {
        $packageItems = [];

        foreach ($items as $packageItem) {
            if (!isset($packageItems[$packageItem['orderItem']])) {
                $packageItems[$packageItem['orderItem']] = 0;
            }

            $packageItems[$packageItem['orderItem']]++;
        }

        $customsItems = [];

        $currency = $this->getCurrency($order);

        foreach ($packageItems as $orderItemId => $quantity) {
            $orderItem = OrderItem::find($orderItemId);

            if ($orderItem) {
                $orderItemWeightInOz = max($this->getWeightInOz($order->customer, $orderItem->weight),
                        0.01) * $quantity;

                $description = $orderItem->product->customs_description;

                if (empty($description)) {
                    $description = $orderItem->name;
                }

                $itemPrice = $orderItem->priceForCustoms();

                $customsItems[] = [
                    'description' => mb_substr($description, 0, 50),
                    'quantity' => $quantity,
                    'value' => max($minValue, $itemPrice * $quantity),
                    'weight' => (string) $orderItemWeightInOz,
                    'hs_tariff_number' => $orderItem->product->hs_code,
                    'code' => mb_substr($orderItem->sku, 0, 20),
                    'origin_country' => $orderItem->product->country->iso_3166_2 ?? 'US',
                    'currency' => $currency,
                    'shipping_cost' => 1,
                ];
            }
        }

        return [
            'customs_certify' => 'true',
            'customs_signer' => $this->getContentsSigner($order->customer),
            'contents_type' => $this->getContentsType($order->customer),
            'contents_explanation' => $this->getContentsExplanation($order->customer),
            'restriction_type' => 'none',
            'eel_pfc' => $this->getEelPfc($order->customer),
            'customs_items' => $customsItems
        ];
    }

    /**
     * @throws GuzzleException
     * @throws ShippingException
     */
    private function fetchRates(Collection $credentials, array $shipmentData, Order $order): array
    {
        $responses = [];

        foreach ($credentials as $credential) {
            $this->setResidentialFlag($credential, $shipmentData['shipment']['to_address']);

            $responses[] = $this->send($credential, 'POST', '/beta/rates', $shipmentData);
        }

        return $responses;
    }
}
