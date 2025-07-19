<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\SetDeliveryConfirmationAction;
use LaravelJsonApi\Eloquent\Fields\Str;

class SetDeliveryConfirmationActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = SetDeliveryConfirmationAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('text_field_value'),
        ]);
    }
}
