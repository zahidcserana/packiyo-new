<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\Invoice;

class InvoiceFactory extends Factory
{
    public function definition()
    {
        return [
            'customer_id' => fn () => Customer::factory()->create()->id,
            'is_finalized' => false,
            'period_start' => today()->subMonth(),
            'period_end' => today()->subDay(),
            'calculated_at' => now(),
            'amount' => $this->faker->randomFloat(2, 1.0, 1000.0),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Invoice &$invoice) {
            if (!$invoice->is_finalized && $this->faker->boolean) {
                $invoice->invoice_number = $this->faker->randomNumber(4);
                $invoice->due_date = $invoice->calculated_at->addWeek();
                $invoice->is_finalized = true;
            }
        });
    }
}
