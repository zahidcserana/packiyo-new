<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\OrderIsManualCondition;
use LaravelJsonApi\Eloquent\Fields\Boolean;

class OrderIsManualConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderIsManualCondition::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Boolean::make('flag_value'),
        ]);
    }
}
