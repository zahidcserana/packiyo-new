<?php

namespace App\Models\CacheDocuments;

use App\Models\CacheDocuments\DataTransferObject\ShipmentInformationDto;
use App\Models\Order;
use App\Models\Shipment;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class ShipmentCacheDocument extends Model implements CacheDocumentInterface
{

    use SoftDeletes;

    protected $connection = 'mongodb';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // Add common fillable properties here
        'three_pl_id', // 3PL ID
        'order',
        // - order ID,
        // - 3PL child ID,
        // - number,
        // - tags
        'shipping_method', //implemented in shipping branch
        // - shipping_method
        //   - name
        //   - method ID
        //   - carrier
        //     - name
        'shipments',
        // - shipments
        //   - shipment
        //   - shipment_tracking_number
        //   - shipment ID
        //   - packages
        //     - shipping box
        //       - box ID
        //       - name
        //     - items
        //       - order item ID
        //       - sku
        //       - quantity
        //       - product tags
        'calculated_billing_rates'
        // calculated_billing_rate
        //      - billing_rate_id
        //      - calculated_at
        //      - charges
    ];

    public static function makeFromModels(Order $order, Shipment ...$shipments): self
    {
        $document = new self();
        $document->three_pl_id = $order->customer->parent->id; //3pl customer
        $tags = $order->tags->map(fn($value) => $value['name']);
        $document->order = [
            'id' => $order->id,
            'number' => $order->number,
            'customer_id' => $order->customer->id, //3pl client customer
            'tags' => $tags->isEmpty() ? [] : $tags->toArray()
        ];
        $document->shipping_method = self::makeShippingMethods(...$shipments); // first shipment on the array. TODO shipments can be done from a different method ?
        $document->shipments = self::makeShipment(...$shipments);
        $document->calculated_billing_rates = [];
        return $document;
    }

    public function addNewShipment(Shipment $shipment): void
    {
        $shipmentData = self::makeShipment($shipment);
        if (empty($this->shipments)) {
            $this->shipments = $shipmentData;
        } else {
            $data = $this->shipments;
            $data[] = $shipmentData;
            $this->shipments = $data;
        }
    }

    public function getOrder(): array
    {
        return $this->order;
    }

    public function getShippingMethod()
    {
        return $this->shipping_method;
    }

    public function get3plCustomerId(): int
    {
        return $this->three_pl_id;
    }

    public function getShipments(): array
    {
        return $this->shipments;
    }

    protected static function makeShipment(Shipment ...$shipments): array
    {
        $shipmentsData = [];
        collect($shipments)->each(function ($shipment) use (&$shipmentsData) {

            $packages = $shipment->packages->map(function ($package) {
                return $package->packageOrderItems->map(function ($packageOrderItem) use ($package) {
                    return [
                        'id' => $packageOrderItem->id,
                        'order_item' => [
                            'id' => $packageOrderItem->orderItem->id,
                            'product_id' => $packageOrderItem->orderItem->product->id,
                            'productTagsName' => $packageOrderItem->orderItem->product->tags->pluck('name')->toArray(),
                            'quantity' => $packageOrderItem->orderItem->quantity,
                            'sku' => $packageOrderItem->orderItem->sku
                        ],
                        'shipping_box' => [
                            'id' => $package->shippingBox->id,
                            'name' => $package->shippingBox->name,
                            'length' => $package->shippingBox->length,
                            'barcode' => $package->shippingBox->barcode,
                            'width' => $package->shippingBox->width,
                            'height' => $package->shippingBox->height,
                            'cost' => $package->shippingBox->getCost(),
                        ]
                    ];
                });
            });

            $shipmentsData [] = [
                'id' => $shipment->id,
                'shipment_tracking_number' => $shipment->getFirstTrackingNumber(),
                'packages' => $packages->toArray(),
                'cost' => $shipment->cost,
                'isGeneric' => $shipment->isGeneric()
            ];

        });

        return $shipmentsData;
    }

    protected static function makeShippingMethods(Shipment ...$shipments): array
    {
        $shippingMethods = [];
        $shipment = collect($shipments)->first();
        if (!$shipment->isGeneric()) {
            $shippingMethods = [
                'id' => $shipment->shippingMethod->id,
                'name' => $shipment->shippingMethod->name,
                'shipping_carrier' => [
                    'id' => $shipment->shippingMethod->shippingCarrier->id,
                    'name' => $shipment->shippingMethod->shippingCarrier->name
                ]
            ];
        }
        return $shippingMethods;
    }

    public function getCalculatedBillingRates()
    {
        return $this->calculated_billing_rates;
    }

    public function getOrderCustomer()
    {
        return $this->getOrder()['customer_id'];
    }
}
