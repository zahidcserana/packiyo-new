<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentLabelFactory extends Factory
{
    public function definition()
    {
        return [
            'shipment_id' => Shipment::factory(),
            'size' => '4x6',
            'url' => $this->faker->url(),
            'content' => null,
            'document_type' => 'pdf',
            'type' => $this->faker->randomElement([ShipmentLabel::TYPE_SHIPPING, ShipmentLabel::TYPE_RETURN]),
            'scac' => 'FDXE',
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
