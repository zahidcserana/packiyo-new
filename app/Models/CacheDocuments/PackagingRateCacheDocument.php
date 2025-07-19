<?php

namespace App\Models\CacheDocuments;


use App\Models\BillingRate;
use App\Models\Customer;
use App\Models\Order;

class PackagingRateCacheDocument extends CacheDocumentAbstract
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
        'billingRate',
        'error'
    ];

    public static function make(
        array $chargeItemDtos,
        array $packages,
        BillingRate $billingRate,
        Customer $customer,
        int $orderId,
        array $shipment
    )
    {
        $self = new self();
        $self->buildFromData($orderId, $customer->id);
        $self->charges = array_map(fn($chargeItemDto) => $chargeItemDto->getData(), $chargeItemDtos);
        $self->packages = $packages;
        $self->billingRate = $self->getRateArray($billingRate);
        $self->shipment_id = $shipment['id'];
        return $self;
    }
}
