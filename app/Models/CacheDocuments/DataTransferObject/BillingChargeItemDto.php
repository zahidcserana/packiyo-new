<?php

namespace App\Models\CacheDocuments\DataTransferObject;

use App\Models\BillingRate;

class BillingChargeItemDto
{
    public array $data;

    public function __construct($description, BillingRate $rate, $settings, $quantity)
    {
        //TODO is there a better way to structure these data ?
        $this->data = [
            'billing_rate_id' => $rate->id ?? null,
            'description' => $description,
            'quantity' => $quantity,
            'charge_per_unit' => $settings['fee'],
            'total_charge' => $quantity * $settings['fee'],
            'purchase_order_item_id' => $settings['purchase_order_item_id'] ?? null,
            'purchase_order_id' => $settings['purchase_order_id'] ?? null,
            'return_item_id' => $settings['return_item_id'] ?? null,
            'package_id' => $settings['package_id'] ?? null,
            'package_item_id' => $settings['package_item_id'] ?? null,
            'shipment_id' => $settings['shipment_id'] ?? null,
            'location_type_id' => $settings['location_type_id'] ?? null
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }
}
