<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\ShipToCountryCondition;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;

class ShipToCountryConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = ShipToCountryCondition::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            ID::make(),
            ArrayList::make('countries', 'text_field_values')->deserializeUsing(
                static fn (array $value) => json_encode($value)
            )->serializeUsing(
                static fn (mixed $value) => static::serializeArrayUsing($value)
            ),
            Str::make('comparison_operator'),
            Boolean::make('case_sensitive')
        ]);
    }
}
