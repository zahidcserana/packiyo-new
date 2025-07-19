<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\TotalOrderAmountCondition;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;

class TotalOrderAmountConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = TotalOrderAmountCondition::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('comparison_operator'),
            Number::make('total', 'number_field_value')
        ]);
    }
}
