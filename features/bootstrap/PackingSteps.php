<?php

use App\Exceptions\ShippingException;
use App\Http\Requests\Packing\StoreRequest;
use App\Models\Order;

trait PackingSteps
{
    protected Order $order;

    protected $packingRequestData = [];

    protected ?ShippingException $shippingException = null;

    /**
     * @When I start packing order :orderNumber
     */
    public function iStartPackingOrder($orderNumber): void
    {
        $customer = $this->getCustomerInScope();
        $this->order = $customer->orders()->where('number', $orderNumber)->firstOrFail();

        $this->packingRequestData = [
            'customer_id' => null,
            'shipping_method_id' => 'generic',
            'packing_state' => [],
            'printer_id' => null,
            'drop_point_id' => null,
            'shipping_contact_information' => $this->order->shippingContactInformation->toArray(),
            'order_items' => []
        ];
    }

    /**
     * @When I take box :shippingBoxName
     */
    public function iTakeBox($shippingBoxName)
    {
        $customer = $this->getWarehouseInScope()->customer;
        $shippingBox = $customer->shippingBoxes()->where('name', $shippingBoxName)->firstOrFail();

        $this->packingRequestData['packing_state'][] = [
            'items' => [],
            'box' => $shippingBox->id,
            'weight' => 0,
            '_length' => $shippingBox->length,
            'width' => $shippingBox->width,
            'height' => $shippingBox->height,
        ];
    }

    /**
     * @When I pack :quantity of :sku from :locationName location
     */
    public function iPackOfFromLocation($quantity, $sku, $locationName)
    {
        $warehouse = $this->getWarehouseInScope();
        $location = $warehouse->locations()->where('name', $locationName)->firstOrFail();
        $orderItem = $this->order->orderItems()->where('sku', $sku)->firstOrFail();

        $this->packingRequestData['order_items'][] = [
            'order_item_id' => $orderItem->id,
            'location_id' => $location->id,
            'tote_id' => null,
            'quantity' => $quantity
        ];

        $packingStateIndex = count($this->packingRequestData['packing_state']) - 1;

        $this->packingRequestData['packing_state'][$packingStateIndex]['weight'] += $orderItem->quantity * $orderItem->weight;

        for ($i = 0; $i < $quantity; $i++) {
            $this->packingRequestData['packing_state'][$packingStateIndex]['items'][] = [
                'orderItem' => $orderItem->id,
                'location' => $location->id,
                'tote' => null,
                'serialNumber' => null
            ];
        }
    }

    /**
     * @Given I ship the order using :shippingMethodName method
     */
    public function iShipTheOrderUsingMethod($shippingMethodName)
    {
        $customer = $this->getWarehouseInScope()->customer;

        if (strtolower($shippingMethodName) != 'generic') {
            $shippingMethod = $customer->shippingMethods()
                ->where('shipping_methods.name', $shippingMethodName)
                ->firstOrFail();

            $this->packingRequestData['shipping_method_id'] = $shippingMethod?->id;
        }

        $this->packingRequestData['customer_id'] = $customer->id;
        $this->packingRequestData['packing_state'] = json_encode($this->packingRequestData['packing_state']);

        app('packing')->packAndShip($this->order, StoreRequest::make($this->packingRequestData));
    }

    /**
     * @Given I try to ship the order using :shippingMethodName method
     */
    public function iShouldNotBeAbleToShipTheOrderUsingMethod($shippingMethodName): void
    {
        try {
            $customer = $this->getWarehouseInScope()->customer;

            if (strtolower($shippingMethodName) != 'generic') {
                $shippingMethod = $customer->shippingMethods()
                    ->where('shipping_methods.name', $shippingMethodName)
                    ->firstOrFail();

                $this->packingRequestData['shipping_method_id'] = $shippingMethod?->id;
            }

            $this->packingRequestData['customer_id'] = $customer->id;
            $this->packingRequestData['packing_state'] = json_encode($this->packingRequestData['packing_state']);

            app('packing')->packAndShip($this->order, StoreRequest::make($this->packingRequestData));
        } catch (ShippingException $e) {
            $this->shippingException = $e;
        }
    }

    /**
     * @Then The shipment should fail with message :message
     */
    public function itShouldFailWithMessage($message): void
    {
        $this->assertNotNull($this->shippingException);
        $this->assertEquals($message, $this->shippingException->getMessage());
    }
}
