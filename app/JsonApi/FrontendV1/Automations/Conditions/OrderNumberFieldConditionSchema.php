<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\OrderNumberFieldCondition;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;

class OrderNumberFieldConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderNumberFieldCondition::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('field_name'),
            Str::make('comparison_operator'),
            Number::make('number_field_value')
        ]);
    }
}
