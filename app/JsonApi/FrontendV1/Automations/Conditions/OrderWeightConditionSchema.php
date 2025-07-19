<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\OrderWeightCondition;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;

class OrderWeightConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderWeightCondition::class;

    /**
     * Get the resource fields.
     *
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('comparison_operator'),
            Number::make('weight','number_field_value'),
            Str::make('unit_of_measure'),
        ]);
    }
}
