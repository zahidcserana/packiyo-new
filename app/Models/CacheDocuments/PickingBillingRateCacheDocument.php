<?php

namespace App\Models\CacheDocuments;

use App\Models\BillingRate;
use App\Models\Customer;

class PickingBillingRateCacheDocument extends CacheDocumentAbstract
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shippedOrderItems',
        'shipment_id',
        'billingRate',
        'error'
    ];

    public static function make(
        array $shippedOrderItems,
        array $chargeItemDtos,
        BillingRate $billingRate,
        Customer $customer,
        int $order_id,
        array $shipment
    ): self
    {
        $self = new self();
        $self->buildFromData($order_id, $customer->id);
        $self->charges = array_map(fn($chargeItemDto)=>$chargeItemDto->getData(), $chargeItemDtos);
        $self->shippedOrderItems = $shippedOrderItems;
        $self->error = null;
        $self->shipment_id = $shipment['id'];
        $self->billingRate = $self->getRateArray($billingRate);
        return $self;
    }
}
