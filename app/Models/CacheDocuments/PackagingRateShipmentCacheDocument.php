<?php

namespace App\Models\CacheDocuments;


use App\Models\BillingRate;
use App\Models\Customer;
use App\Models\Order;

class PackagingRateShipmentCacheDocument extends ShipmentCacheDocumentAbstract
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // Add common fillable properties here
        'packages',
        'shipment_id',
        'billing_rate',
        'error'
    ];

    public static function make(
        array $chargeItemDtos,
        array $packages,
        BillingRate $billing_rate,
        Customer $customer,
        int $orderId,
        array $shipment
    )
    {
        $self = new self();
        $self->buildFromData($orderId, $customer->id);
        $self->charges = array_map(fn($chargeItemDto) => $chargeItemDto->getData(), $chargeItemDtos);
        $self->packages = $packages;
        $self->billing_rate = $self->getRateArray($billing_rate);
        $self->shipment_id = $shipment['id'];
        return $self;
    }

    public function getBillingRate()
    {
        return $this->billing_rate;
    }
}
