<?php

namespace Database\Factories;

use App\Models\{LocationType, Location, Warehouse};
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    public const NAME_PADDING_LENGTH = 4;

    protected int $locationCount = 0;

    public function definition()
    {
        return [
            'warehouse_id' => fn () => Warehouse::first()->id,
            'location_type_id' => fn () => LocationType::factory()->create()->id,
            'name' => $this->faker->word,
            'pickable' => $this->faker->numberBetween(0, 1),
            'sellable' => $this->faker->numberBetween(0, 1),
            'is_receiving' => false,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Location $location) {
            $this->locationCount += 1;
            $locationCount = str_pad($this->locationCount, self::NAME_PADDING_LENGTH, '0', STR_PAD_LEFT);

            if ($location->locationType) {
                $location->name = $location->locationType->name . '-' . $locationCount;
            } else {
                $location->name = empty($location->name) ?'Generic' . '-' . $locationCount : $location->name;
            }
        });
    }
}
