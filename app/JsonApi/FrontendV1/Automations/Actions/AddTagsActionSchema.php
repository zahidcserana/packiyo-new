<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\AddTagsAction;
use LaravelJsonApi\Eloquent\Fields\ArrayList;

class AddTagsActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = AddTagsAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            ArrayList::make('tags')->deserializeUsing(
                static fn (array $value) => json_encode($value)
            )->serializeUsing(
                static fn (mixed $value) => json_decode($value->pluck('name'))
            )
        ]);
    }
}
