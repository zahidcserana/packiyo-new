<?php

namespace App\Traits;

use App\Exceptions\BillingException;
use App\Models\InvoiceLineItem;

trait InvoiceTrait
{

    /**
     * @throws BillingException
     */
    public function createInvoiceLineItemWithParams($description, $invoice, $rate, $settings, $quantity, $periodEnd)
    {
        if (!key_exists('fee', $settings)) {
            throw new BillingException(__('The billing rate has no base fee.'));
        }

        return $this->createLineItem([
            'invoice_id' => $invoice->id,
            'billing_rate_id' => $rate->id ?? null,
            'description' => $description,
            'quantity' => $quantity,
            'charge_per_unit' => $settings['fee'],
            'total_charge' => $quantity * $settings['fee'],
            'period_end' => $periodEnd,
            'purchase_order_item_id' => $settings['purchase_order_item_id'] ?? null,
            'purchase_order_id' => $settings['purchase_order_id'] ?? null,
            'return_item_id' => $settings['return_item_id'] ?? null,
            'package_id' => $settings['package_id'] ?? null,
            'package_item_id' => $settings['package_item_id'] ?? null,
            'shipment_id' => $settings['shipment_id'] ?? null,
            'location_type_id' => $settings['location_type_id'] ?? null
        ]);
    }

    /**
     * @throws BillingException
     */
    public function createLineItem(array $data)
    {
        return InvoiceLineItem::create($data);
    }
}
