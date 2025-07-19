<?php

namespace App\Components\Wholesale\EDIProviders\Crstl;

use App\Exceptions\WholesaleException;
use App\Models\CustomerSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageOrderItem;
use App\Models\Product;
use App\Models\Shipment;
use Illuminate\Support\Collection;

class PackingLabelsSerializer
{
    protected const TRANSPORTATION_TYPE_CODE_MOTOR = 'MOTOR';
    protected const TRANSPORTATION_TYPE_CODE_AIR = 'AIR';
    protected const TRANSPORTATION_TYPE_CODE_LTL = 'LTL';
    protected const TRANSPORTATION_TYPE_CODE_PRIVATE_PARCEL = 'PRIVATE_PARCEL';

    protected array $shipments;
    protected Shipment $referenceShipment;
    protected Collection $packages;

    public function __construct(protected Order $order, Shipment ...$shipments)
    {
        $this->shipments = $shipments;
        $this->referenceShipment = $this->referenceShipment(...$shipments);

        $this->packages = collect($this->shipments)->flatMap(
            fn (Shipment $shipment) => $shipment->packages
        );
    }

    private function referenceShipment(Shipment ...$shipments): Shipment
    {
        return $shipments[0];
    }

    public function serialize(): array
    {
        return [
            'purchase_order_id' => (string) $this->order->external_id,
            'integration_partner' => 'packiyo',
            'id' => (string) $this->order->id,
            'purchase_order_number' => $this->order->number,
            'carrier' => $this->serializeCarrier(),
            'measurements' => $this->serializeMeasurements(),
            'dates' => $this->serializeDates(),
            'terms' => ['payment' => 'COLLECT'],
            'packages' => $this->serializePackages()
        ];
    }

    protected function serializeCarrier(): array
    {
        $data = [
            'name' => $this->serializeCarrierName(),
            'transportation_method_type_code' => $this->serializeTransportationMethodTypeCode(),
            // These must be sent, even empty.
            'scac' => '',
            'tracking_number' => '',
            // TODO: Required for valid ASN on the Crstl sandbox - but are these required for GS1-128 labels?
            // 'bill_of_lading_number' => 'BOLXYZ123', // TODO: How to get these?
            // 'carrier_pro_number' => 'CPNXYZ123' // TODO: How to get these?
        ];

        $scac = $this->referenceShipment->shipmentLabels->first()->scac; // TODO first shipment?

        if ($scac) {
            $data['scac'] = $scac;
        }

        $trackingNumber = $this->referenceShipment->getFirstTrackingNumber(); // TODO first shipment?

        if ($trackingNumber) {
            $data['tracking_number'] = $trackingNumber;
        }

        return $data;
    }

    protected function serializeCarrierName(): string
    {
        return $this->referenceShipment->shippingMethod ? $this->referenceShipment->shippingMethod->shippingCarrier->name : 'Generic'; // TODO first shipment or Order level?
    }

    protected function serializeTransportationMethodTypeCode(): string
    {
        $code = null;

        // TODO: There is a lot of guesswork going on here. Implement properly once we have more data.
        if ($this->referenceShipment->is_freight) {
            $code = static::TRANSPORTATION_TYPE_CODE_LTL;
        // } elseif ($this->shipment->shipmentTrackings->isNotEmpty()) {
        //     $code = static::TRANSPORTATION_TYPE_CODE_PRIVATE_PARCEL;
        // } elseif (?) {
        //     $code = static::TRANSPORTATION_TYPE_CODE_AIR;
        } else {
            $code = static::TRANSPORTATION_TYPE_CODE_MOTOR;
        }

        return $code;
    }

    protected function serializeMeasurements(): array
    {
        return [
            'weight' => (string) $this->packages->sum(fn (Package $package) => $package->getTotalWeight()),
            // Deliberately not using $this->shipment->order->orderItems because of partial shipments.
            'weight_uom' => strtoupper(customer_settings($this->order->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT, 'lb')),
            'volume' => (string) $this->packages->sum(fn (Package $package) => $package->getVolumeInOz()),
            'volume_uom' => 'OZ', // Only option currently.
            'lading_quantity' => (string) $this->packages->count(), // TODO: Actually, these would be pallets. Also, why string?
            'lading_quantity_uom' => 'CARTON', // TODO: This should be dynamic.
        ];
    }

    protected function serializeDates(): array
    {
        return [
            'shipped_date' => $this->referenceShipment->created_at->toDateString(),
            // We're only using the referenceShipment created_at because of requirements on Crstl's side
            'estimated_delivery_date' => $this->order->scheduled_delivery?->toDateString() ?? $this->referenceShipment->created_at->toDateString(),
        ];
    }

    protected function serializePackages(): array
    {
        return $this->packages->map(
            fn (Package $package) => (new PackageSerializer($package))->serialize()
        )->toArray();
    }
}

class PackageSerializer
{
    protected const TYPE_CARTON = 'CARTON';
    protected const TYPE_PALLET = 'PALLET'; // Only be used if UOM is pallet for single GS1-128 per pallet.

    protected Package $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function serialize(): array
    {
        return [
            'id' => (string) $this->package->id,
            'type' => static::TYPE_CARTON, // TODO: Revisit when we have product cases.
            'measurements' => $this->serializeMeasurements(),
            'products' => $this->serializeProducts()
        ];
    }

    protected function serializeMeasurements(): array
    {
        return [
            'weight' => (string) $this->package->getTotalWeight(),
            'weight_uom' => strtoupper(customer_settings($this->package->order->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT, 'lb')),
            'volume' => (string) $this->package->getVolumeInOz(),
            'volume_uom' => 'OZ', // Only option currently.
        ];
    }

    protected function serializeProducts(): array
    {
        return $this->package->packageOrderItems->map(
            fn (PackageOrderItem $packageOrderItem) => (new ProductSerializer($packageOrderItem))->serialize()
        )->toArray();
    }
}

class ProductSerializer
{
    protected const UOM_EACH = 'EACH';
    protected const UOM_PACK = 'PACK';

    protected OrderItem $orderItem;
    protected int $quantity;

    public function __construct(PackageOrderItem $packageOrderItem)
    {
        $this->orderItem = static::getOrderItem($packageOrderItem);
        $this->quantity = static::getQuantity($packageOrderItem);
    }

    protected static function getOrderItem(PackageOrderItem $packageOrderItem): OrderItem
    {
        return $packageOrderItem->orderItem->parentOrderItem ?? $packageOrderItem->orderItem;
    }

    protected static function getQuantity(PackageOrderItem $packageOrderItem): int
    {
        return $packageOrderItem->orderItem->parentOrderItem
            ? static::getCaseSize($packageOrderItem->orderItem->parentOrderItem->product, $packageOrderItem->quantity)
            : $packageOrderItem->quantity;
    }

    protected static function getCaseSize(Product $product, int $eachesQuantity): int
    {
        if (!$product->isKit()) {
            throw new WholesaleException('Products used as cases must be kits.');
        } elseif ($product->components->count() != 1) {
            throw new WholesaleException('Kits used as product cases must have a single component SKU.');
        } elseif (!is_divisible($eachesQuantity, $product->components->first()->quantity)) {
            throw new WholesaleException('The quantity of packed eaches does not respect the case size.');
        }

        return $eachesQuantity / $product->components->first()->quantity;
    }

    public function serialize(): array
    {
        $data = [
            'upc' => (string) $this->orderItem->product->barcode,
            'sku' => $this->orderItem->sku,
            'name' => $this->orderItem->name,
            'quantity' => (string) $this->quantity,
            'uom' => $this->serializeUOM(),
            'price' => (string) $this->orderItem->price,
            'external_id' => $this->orderItem->external_id
        ];

        $lotNumber = $this->serializeLotNumber();

        if ($lotNumber) {
            $data['lot_number'] = $lotNumber;
        }

        $expirationDate = $this->serializeExpirationDate();

        if ($expirationDate) {
            $data['expiration_date'] = $expirationDate;
        }

        return $data;
    }

    protected function serializeUOM(): string
    {
        return $this->orderItem->product->isKit() ? static::UOM_PACK : static::UOM_EACH;
    }

    protected function serializeLotNumber(): ?string
    {
        $lot = $this->orderItem->lot;

        return is_null($lot) ? null : $lot->name;
    }

    protected function serializeExpirationDate(): ?string
    {
        $lot = $this->orderItem->lot;

        return is_null($lot) ? null : $lot->expiration_date->toDateString();
    }
}
