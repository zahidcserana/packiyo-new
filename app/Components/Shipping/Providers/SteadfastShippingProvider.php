<?php

namespace App\Components\Shipping\Providers;

use App\Components\{ReturnComponent, ShippingComponent};
use App\Exceptions\ShippingException;
use App\Http\Requests\{Packing\PackageItemRequest, Shipment\ShipItemRequest};
use App\Interfaces\{BaseShippingProvider, ShippingProviderCredential};
use App\Models\{
    CustomerSetting,
    Order,
    OrderItem,
    Package,
    Return_,
    Shipment,
    ShipmentLabel,
    ShippingCarrier,
    ShippingMethod,
    SteadfastCredential
};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use GuzzleHttp\{Client, Exception\GuzzleException, Exception\RequestException};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\{Arr, Facades\Log};
use Picqer\Barcode\BarcodeGeneratorPNG;

class SteadfastShippingProvider implements BaseShippingProvider
{
    public const INTEGRATION_NAME = 'Steadfast';
    public const SHIPPING_METHOD_NAME = 'Standard Delivery';

    /**
     * Create ShippingCarrier + ShippingMethod records for every SteadfastCredential.
     */
    public function getCarriers(?ShippingProviderCredential $credential = null): void
    {
        $carrierService = array_search(get_class($this), ShippingComponent::SHIPPING_CARRIERS);
        $credentials = new Collection();

        if (!is_null($credential)) {
            $credentials->add($credential);
        } else {
            $credentials = SteadfastCredential::all();
        }

        foreach ($credentials as $cred) {
            $shippingCarrier = $cred->shippingCarriers()
                ->withTrashed()
                ->where('customer_id', $cred->customer_id)
                ->where('carrier_service', $carrierService)
                ->first();

            if (!$shippingCarrier) {
                $shippingCarrier = ShippingCarrier::create([
                    'customer_id'     => $cred->customer_id,
                    'carrier_service' => $carrierService,
                    'integration'     => self::INTEGRATION_NAME,
                    'settings'        => [],
                ]);

                $shippingCarrier->credential()->associate($cred);
            }

            $shippingCarrier->integration = self::INTEGRATION_NAME;
            $shippingCarrier->name        = 'SF';
            $shippingCarrier->save();
            $shippingCarrier->restore();

            $shippingMethod = ShippingMethod::withTrashed()
                ->where('shipping_carrier_id', $shippingCarrier->id)
                ->whereJsonContains('settings', ['shipping_method' => self::SHIPPING_METHOD_NAME])
                ->first();

            if (!$shippingMethod) {
                $shippingMethod = ShippingMethod::create([
                    'shipping_carrier_id' => $shippingCarrier->id,
                ]);
            }

            $shippingMethod->name     = self::SHIPPING_METHOD_NAME;
            $shippingMethod->settings = ['shipping_method' => self::SHIPPING_METHOD_NAME];
            $shippingMethod->save();
            $shippingMethod->restore();
        }
    }

    /**
     * Create a shipment via the Steadfast API.
     *
     * @throws ShippingException|GuzzleException
     */
    public function ship(Order $order, $storeRequest, ?ShippingMethod $shippingMethod = null): array
    {
        $input = $storeRequest->all();

        if (is_null($shippingMethod)) {
            $shippingMethod = empty($input['shipping_method_id'])
                ? $order->shippingMethod
                : ShippingMethod::find($input['shipping_method_id']);
        }

        $orderItemsToShip   = [];
        $packageItemRequests = [];

        foreach ($input['order_items'] as $record) {
            $shipItemRequest      = ShipItemRequest::make($record);
            $orderItem            = OrderItem::find($record['order_item_id']);
            $orderItemsToShip[]   = ['orderItem' => $orderItem, 'shipRequest' => $shipItemRequest];
        }

        foreach (json_decode($input['packing_state'], true) as $packingStateItem) {
            $packageItemRequests[] = PackageItemRequest::make($packingStateItem);
        }

        $body     = $this->buildCreateOrderBody($order, $input);
        $response = $this->send($shippingMethod->shippingCarrier->credential, 'POST', '/create_order', $body);

        if (empty($response['data']['consignment_id'])) {
            throw new ShippingException(
                __('Steadfast order creation failed: :msg', ['msg' => $response['message'] ?? 'Unknown error'])
            );
        }

        $consignmentId = $response['data']['consignment_id'];
        $trackingCode  = $response['data']['tracking_code'] ?? $consignmentId;
        $deliveryFee   = (float) ($response['data']['delivery_fee'] ?? 0);

        $shipment = app('shipping')->createShipment($order, $shippingMethod, $input, $deliveryFee, $consignmentId);

        app('shipment')->createContactInformation($order->shippingContactInformation->toArray(), $shipment);

        foreach ($orderItemsToShip as $item) {
            app('shipment')->shipItem($item['shipRequest'], $item['orderItem'], $shipment);
        }

        if ($order->shipments->count() === 1) {
            app('shipment')->shipVirtualProducts($order, $shipment);
        }

        foreach ($packageItemRequests as $packageItemRequest) {
            app('shipping')->createPackage($order, $packageItemRequest, $shipment);
        }

        $this->storeShipmentLabelAndTracking($shipment, $trackingCode);

        $order->shipping_method_id = $shippingMethod->id;
        $order->save();

        return [$shipment];
    }

    /**
     * Cancel a shipment at Steadfast. Falls back to local void if the consignment ID is missing.
     */
    public function void(Shipment $shipment): array
    {
        $consignmentId = $shipment->external_shipment_id;

        if ($consignmentId) {
            try {
                $credential = $shipment->shippingMethod->shippingCarrier->credential;
                $this->send($credential, 'POST', '/cancel_order/' . $consignmentId);
            } catch (ShippingException $e) {
                Log::warning('[Steadfast] cancel_order API failed, voiding locally.', ['error' => $e->getMessage()]);
            }
        }

        $shipment->voided_at = Carbon::now();
        $shipment->saveQuietly();

        return ['success' => true, 'message' => __('Shipment successfully voided.')];
    }

    /**
     * Create a return (reverse) shipment.
     * Steadfast does not have a dedicated return-order endpoint; we generate a local return label.
     */
    public function return(Order $order, $storeRequest): Return_
    {
        $input           = $storeRequest->all();
        $input['number'] = Return_::getUniqueIdentifier(ReturnComponent::NUMBER_PREFIX, $input['warehouse_id']);

        $return = app('return')->createReturn($order, $input);

        app('return')->storeReturnLabel(
            $return,
            base64_encode($this->generateReturnLabel($return)),
            null,
            null,
            'pdf'
        );

        return $return;
    }

    /**
     * Steadfast does not expose a public rates API; rates are fixed per merchant agreement.
     */
    public function getShippingRates(Order $_order, array $_input, array $_params = []): array
    {
        return [];
    }

    public function getCheapestShippingRates(Order $_order, array $_input, array $_params = []): array
    {
        return [];
    }

    public function manifest(ShippingCarrier $_shippingCarrier): void
    {
        // Not implemented — Steadfast does not support manifests via API.
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build the flat JSON body for POST /create_order.
     */
    private function buildCreateOrderBody(Order $order, array $input): array
    {
        $contact  = $order->shippingContactInformation;
        $address  = trim(implode(', ', array_filter([
            $contact->address,
            $contact->address2,
            $contact->city,
            $contact->state,
        ])));

        $totalWeight = 0;
        $itemDescriptions = [];

        foreach ($input['order_items'] as $record) {
            $orderItem = OrderItem::find($record['order_item_id']);

            if ($orderItem) {
                $totalWeight       += (float) $orderItem->product->weight * ($record['quantity'] ?? 1);
                $itemDescriptions[] = $orderItem->name;
            }
        }

        // COD amount: use explicit input if provided, otherwise use the order total.
        $codAmount = (float) Arr::get($input, 'cod_amount', $order->total ?? 0);

        return [
            'invoice'          => ltrim((string) $order->number, '#'),
            'recipient_name'   => $contact->name,
            'recipient_phone'  => $contact->phone,
            'recipient_address'=> $address,
            'cod_amount'       => $codAmount,
            'note'             => $order->packing_note ?? '',
            'item_description' => mb_substr(implode(', ', array_unique($itemDescriptions)), 0, 255),
        ];
    }

    /**
     * Store the tracking number and generate/store the shipping label PDF.
     */
    private function storeShipmentLabelAndTracking(Shipment $shipment, string $trackingCode): void
    {
        $trackingNumber = ltrim($trackingCode, '#');

        app('shipping')->storeShipmentTracking(
            $shipment,
            $trackingNumber,
            'https://steadfast.com.bd/t/' . $trackingNumber
        );

        foreach ($shipment->packages as $package) {
            app('shipping')->storeShipmentLabel(
                $shipment,
                base64_encode($this->generateLabel($package)),
                null,
                null
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
     * Generate a PDF shipping label using the generic label template.
     */
    private function generateLabel(Package $package, string $type = ShipmentLabel::TYPE_SHIPPING): string
    {
        $generator = new BarcodeGeneratorPNG();

        $shipFromContactInformation = $package->shipment->order->customer->shipFromContactInformation
            ?? $package->shipment->order->customer->parent?->shipFromContactInformation
            ?? $package->packageOrderItems->first()?->location?->warehouse?->contactInformation;

        $data = [
            'senderCustomerContactInformation' => $package->shipment->order->customer->contactInformation,
            'senderContactInformation'         => $shipFromContactInformation,
            'receiverCustomerContactInformation'=> $package->shipment->contactInformation,
            'receiverContactInformation'        => $package->shipment->contactInformation,
            'barcode'                           => $generator->getBarcode($package->shipment->order->number, $generator::TYPE_CODE_128),
            'barcodeNumber'                     => $package->shipment->order->number,
            'trackingNumber'                    => $package->shipment->shipmentTrackings->first()?->tracking_number ?? '',
        ];

        $paperWidth  = paper_width($package->shipment->order->customer_id, 'label');
        $paperHeight = paper_height($package->shipment->order->customer_id, 'label');

        if ($type === ShipmentLabel::TYPE_RETURN) {
            $returnTo = $package->shipment->order->customer->returnToContactInformation
                ?? $package->shipment->order->customer->parent?->returnToContactInformation
                ?? $data['senderContactInformation'];

            $data['senderContactInformation']          = $data['receiverContactInformation'];
            $data['senderCustomerContactInformation']  = $data['receiverCustomerContactInformation'];
            $data['receiverContactInformation']        = $returnTo;
            $data['receiverCustomerContactInformation']= $data['senderCustomerContactInformation'];
        }

        return Pdf::loadView('pdf.genericlabel', $data)
            ->setPaper([0, 0, $paperWidth, $paperHeight])
            ->output();
    }

    /**
     * Generate a PDF return label.
     */
    private function generateReturnLabel(Return_ $return): string
    {
        $generator = new BarcodeGeneratorPNG();

        $returnToContactInformation = $return->order->customer->returnToContactInformation
            ?? $return->order->customer->parent?->returnToContactInformation
            ?? $return->order->customer->warehouses->first()?->contactInformation;

        $data = [
            'senderCustomerContactInformation' => $return->order->shippingContactInformation,
            'senderContactInformation'         => $return->order->shippingContactInformation,
            'receiverCustomerContactInformation'=> $return->order->customer->contactInformation,
            'receiverContactInformation'        => $returnToContactInformation,
            'barcode'                           => $generator->getBarcode((string) $return->id, $generator::TYPE_CODE_128),
            'barcodeNumber'                     => $return->id,
            'type'                              => 'Return',
        ];

        $paperWidth  = paper_width($return->order->customer_id, 'label');
        $paperHeight = paper_height($return->order->customer_id, 'label');

        return Pdf::loadView('pdf.genericlabel', $data)
            ->setPaper([0, 0, $paperWidth, $paperHeight])
            ->output();
    }

    /**
     * Execute an HTTP request against the Steadfast API.
     * Auth is via static Api-Key / Secret-Key headers — no token exchange needed.
     *
     * @throws ShippingException|GuzzleException
     */
    private function send(SteadfastCredential $credential, string $method, string $endpoint, ?array $data = null): array
    {
        $baseUrl = rtrim($credential->api_base_url, '/');
        $url     = $baseUrl . $endpoint;

        Log::info('[Steadfast] request', [
            'credential_id' => $credential->id,
            'method'        => $method,
            'url'           => $url,
            'body'          => $data,
        ]);

        $client = new Client([
            'headers' => [
                'Api-Key'      => $credential->api_key,
                'Secret-Key'   => $credential->secret_key,
                'Content-Type' => 'application/json',
            ],
        ]);

        try {
            $response = $client->request(
                $method,
                $url,
                $data !== null ? ['body' => json_encode($data)] : []
            );

            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            Log::info('[Steadfast] response', ['body' => $body]);

            return $body;
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()?->getBody();

            Log::error('[Steadfast] request failed', [
                'url'      => $url,
                'response' => $responseBody,
            ]);

            throw new ShippingException($responseBody ?: $e->getMessage());
        }
    }
}
