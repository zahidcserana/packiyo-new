<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\AddPackingNoteAction;
use LaravelJsonApi\Eloquent\Fields\Str;

class AddPackingNoteActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = AddPackingNoteAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Str::make('text'),
            Str::make('insert_method')
        ]);
    }
}
