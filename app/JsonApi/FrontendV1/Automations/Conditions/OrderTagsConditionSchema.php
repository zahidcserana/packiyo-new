<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\OrderTagsCondition;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Str;

class OrderTagsConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderTagsCondition::class;

    /**
     * Get the resource fields.
     *
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('applies_to'),
            ArrayList::make('tags')->deserializeUsing(
                static fn (array $value) => json_encode($value)
            )->serializeUsing(
                static fn (mixed $value) => json_decode($value->pluck('name'))
            )
        ]);
    }
}
