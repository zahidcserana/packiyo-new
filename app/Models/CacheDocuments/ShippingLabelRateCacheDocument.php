<?php

namespace App\Models\CacheDocuments;


use App\Models\BillingRate;
use App\Models\Customer;
use App\Models\Order;

class ShippingLabelRateCacheDocument extends CacheDocumentAbstract
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // Add common fillable properties here
        'shipments',
        'shipment_id',
        'billingRate',
        'error'
    ];


    public static function make(
        array $chargeItemDtos,
        array $shipment,
        BillingRate $billingRate,
        Customer $customer,
        int $orderId
    )
    {
        $self = new self();
        $self->buildFromData($orderId, $customer->id);
        $self->charges = array_map(fn($chargeItemDto) => $chargeItemDto->getData(), $chargeItemDtos);
        $self->shipments = $shipment;
        $self->shipment_id = $shipment['id'];
        $self->billingRate = $self->getRateArray($billingRate);
        return $self;
    }
}
