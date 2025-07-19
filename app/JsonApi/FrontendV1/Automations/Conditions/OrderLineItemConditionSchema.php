<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\JsonApi\PublicV1\Products\ProductSchema;
use App\Models\AutomationConditions\OrderLineItemCondition;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;

class OrderLineItemConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderLineItemCondition::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('applies_to'),
            Number::make('number_field_value'),
            Str::make('comparison_operator'),
            HasMany::make('matches_products')->type('products'),
        ]);
    }
}
