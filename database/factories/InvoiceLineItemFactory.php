<?php

namespace Database\Factories;

use App\Models\BillingRate;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceLineItemFactory extends Factory
{
    public function definition()
    {
        return [
            'invoice_id' => function () {
                return Invoice::factory()->create()->id;
            },
            'billing_rate_id' => function () {
                return BillingRate::factory()->create()->id;
            },
            'description' => '',
            'quantity' => 1,
            'charge_per_unit' => 1.0,
            'total_charge' => 1 * 1.0,
            // 'period_end',
            'created_at' => now(),
            'updated_at' => now(),
            'shipment_id' => Shipment::factory()->create()->id
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (InvoiceLineItem &$lineItem) {
            $lineItem->period_end = $lineItem->invoice->period_end;
        });
    }
}
