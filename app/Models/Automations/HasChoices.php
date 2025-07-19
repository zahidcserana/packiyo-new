<?php

namespace App\Models\Automations;

use Illuminate\Support\Collection;

trait HasChoices
{
    public static function choices(array $remove = []): array
    {
        return collect(self::cases())
            ->filter(fn (mixed $value) => !in_array($value, $remove))
            ->when(method_exists(self::class, 'label'),
                fn (Collection $collection) => $collection->mapWithKeys(
                    fn (self $value) => [$value->value => $value->label()]
                ),
                fn (Collection $collection) => $collection->pluck('value')
            )
            ->toArray();
    }
}
