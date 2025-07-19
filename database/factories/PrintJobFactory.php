<?php

namespace Database\Factories;

use App\Models\Printer;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrintJobFactory extends Factory
{
    public function definition()
    {
        return [
            'object_type' => Shipment::class,
            'object_id' =>  fn () => Shipment::factory()->create()->id,
            'url' => $this->faker->url,
            'printer_id' =>  fn () => Printer::factory()->create()->id,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
