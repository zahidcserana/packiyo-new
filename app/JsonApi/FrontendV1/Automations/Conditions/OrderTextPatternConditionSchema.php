<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\OrderTextPatternCondition;
use LaravelJsonApi\Eloquent\Fields\Str;

class OrderTextPatternConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderTextPatternCondition::class;

    /**
     * Get the resource fields.
     *
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('field_name'),
            Str::make('text_pattern'),
            Str::make('comparison_operator'),
        ]);
    }
}
