<?php

namespace Database\Factories;

use App\Models\{Tote, Warehouse};
use Illuminate\Database\Eloquent\Factories\Factory;

class ToteFactory extends Factory
{
    public const NAME_PADDING_LENGTH = 4;

    protected int $toteCount = 0;

    public function definition()
    {
        $name = $this->faker->word;

        return [
            'name' => $name,
            'barcode' => $name,
            'warehouse_id' => Warehouse::all()->random()->id,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Tote $tote) {
            $this->toteCount += 1;

            if ($tote->toteType) {
                $toteCount = str_pad($this->toteCount, self::NAME_PADDING_LENGTH, '0', STR_PAD_LEFT);
                $tote->name = $tote->name . '-' . $toteCount;
                $tote->barcode = $tote->barcode . '-' . $toteCount;
            }
        });
    }
}
