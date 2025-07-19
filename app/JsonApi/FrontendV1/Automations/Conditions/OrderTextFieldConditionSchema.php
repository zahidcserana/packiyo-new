<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use App\Models\AutomationConditions\OrderTextFieldCondition;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\Str;

class OrderTextFieldConditionSchema extends AutomationConditionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderTextFieldCondition::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('field_name'),
            ArrayList::make('text_field_values')->deserializeUsing(
                static fn (array $value) => json_encode($value)
            )->serializeUsing(
                static fn (mixed $value) => static::serializeArrayUsing($value)
            ),
            Str::make('comparison_operator'),
            Boolean::make('case_sensitive')
        ]);
    }

    protected static function serializeArrayUsing(mixed $value): array
    {
        return is_array($value) ? $value : json_decode($value, true);
    }
}
