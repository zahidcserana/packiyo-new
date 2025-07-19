<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\OrderLineItemsCondition;
use App\Models\AutomationConditions\QuantityOrderItemsCondition;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;

class QuantityOrderItemsConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = QuantityOrderItemsCondition::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Number::make('quantity', 'number_field_value'),
            Str::make('comparison_operator')
        ]);
    }
}
