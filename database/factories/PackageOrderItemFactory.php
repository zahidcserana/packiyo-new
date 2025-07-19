<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\Tote;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageOrderItemFactory extends Factory
{
    public function definition()
    {
        return [
            'order_item_id' => fn () => OrderItem::factory()->create()->id,
            'package_id' => fn () => Package::factory()->create()->id,
            'quantity' => $this->faker->numberBetween(1, 100),
            'serial_number' => (string) $this->faker->randomNumber(7) . (string) $this->faker->randomNumber(7),
            'location_id' => fn () => Location::factory()->create()->id,
            'tote_id' => fn () => Tote::factory()->create()->id,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
