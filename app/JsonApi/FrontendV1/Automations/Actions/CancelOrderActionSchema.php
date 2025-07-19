<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\CancelOrderAction;
use LaravelJsonApi\Eloquent\Fields\Boolean;

class CancelOrderActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = CancelOrderAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Boolean::make('ignore_fulfilled'),
        ]);
    }
}
