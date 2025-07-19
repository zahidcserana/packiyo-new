<?php

namespace App\Components\Shipping\Providers;

use App\Http\Requests\{Packing\PackageItemRequest, Shipment\ShipItemRequest};
use App\Interfaces\{BaseShippingProvider, ShippingProviderCredential};
use App\Models\{CustomerSetting,
    Order,
    OrderItem,
    Package,
    Return_,
    Shipment,
    ShipmentLabel,
    ShippingCarrier,
    ShippingMethod};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Picqer\Barcode\BarcodeGeneratorPNG;

class GenericShippingProvider implements BaseShippingProvider
{
    public function getCarriers(ShippingProviderCredential $credential = null)
    {
        return true;
    }

    /**
     * @param Order $order
     * @param $storeRequest
     * @param ShippingMethod|null $shippingMethod
     * @return array
     */
    public function ship(Order $order, $storeRequest, ShippingMethod $shippingMethod = null): array
    {
        $input = $storeRequest->all();

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

        $shipment = app('shipping')->createShipment($order, null, $input);

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

        $this->storeShipmentLabelAndTracking($shipment);

        return [$shipment];
    }

    /**
     * @param Order $order
     * @param $storeRequest
     * @return Return_
     */
    public function return(Order $order, $storeRequest): Return_
    {
        $input = $storeRequest->all();

        $return = app('return')->createReturn($order, $input);

        $this->storeOnlyReturnLabel($return);

        return $return;
    }


    /**
     * @param $return
     * @return void
     */
    private function storeOnlyReturnLabel($return): void
    {
        app('return')->storeReturnLabel(
            $return,
            base64_encode($this->generateReturnLabel($return)),
            null,
            null,
            'pdf'
        );
    }

    /**
     * @param Shipment $shipment
     * @return void
     */
    public function regenerateShipmentLabels(Shipment $shipment): void
    {
        foreach ($shipment->shipmentLabels as $shipmentLabel) {
            $shipmentLabel->content = base64_encode($this->generateLabel($shipment->packages->first(), $shipmentLabel->type));
            $shipmentLabel->update();
        }
    }

    /**
     * @param Shipment $shipment
     * @return void
     */
    private function storeShipmentLabelAndTracking(Shipment $shipment): void
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

    /**
     * @param Shipment $shipment
     * @return array
     */
    public function void(Shipment $shipment): array
    {
        $shipment->voided_at = Carbon::now();

        $shipment->saveQuietly();

        return ['success' => true, 'message' => __('Shipment successfully voided.')];
    }

    /**
     * @param Return_ $return
     * @return string
     */
    private function generateReturnLabel(Return_ $return): string
    {
        $generator = new BarcodeGeneratorPNG();
        $returnToContactInformation = $return->order->customer->returnToContactInformation;

        if (empty($returnToContactInformation)) {
            $returnToContactInformation = $return->order->customer->parent?->returnToContactInformation;

            if (empty($returnToContactInformation)) {
                $returnToContactInformation = $return->order->customer->warehouses->first()->contactInformation;
            }
        }

        $order = $return->order;

        $data = [
            'senderCustomerContactInformation' => $return->order->shippingContactInformation,
            'senderContactInformation' => $return->order->shippingContactInformation,
            'receiverCustomerContactInformation' => $return->order->customer->contactInformation,
            'receiverContactInformation' => $returnToContactInformation,
            'barcode' => $generator->getBarcode($return->id, $generator::TYPE_CODE_128),
            'barcodeNumber' => $return->id,
            'type' => 'Return',
        ];

        $paperWidth = paper_width($order->customer_id, 'label');
        $paperHeight = paper_height($order->customer_id, 'label');

        return PDF::loadView('pdf.genericlabel', $data)
            ->setPaper([0, 0, $paperWidth, $paperHeight])
            ->output();
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
        // TODO: Implement getShippingRates() method.
        return [];
    }

    /**
     * @param Order $order
     * @param array $input
     * @param array $params
     * @return array
     */
    public function getCheapestShippingRates(Order $order, array $input, array $params = []): array
    {
        // TODO: Implement getCheapestShippingRates() method.
        return [];
    }
}
