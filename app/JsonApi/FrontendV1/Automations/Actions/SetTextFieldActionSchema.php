<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\SetTextFieldAction;
use LaravelJsonApi\Eloquent\Fields\Str;

class SetTextFieldActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = SetTextFieldAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('field_name'),
            Str::make('text_field_value')
        ]);
    }
}
