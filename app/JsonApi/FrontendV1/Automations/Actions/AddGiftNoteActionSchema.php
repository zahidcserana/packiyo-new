<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\AddGiftNoteAction;
use LaravelJsonApi\Eloquent\Fields\Boolean;

class AddGiftNoteActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = AddGiftNoteAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Boolean::make('gift_note', 'text_field_value'),
        ]);
    }
}
