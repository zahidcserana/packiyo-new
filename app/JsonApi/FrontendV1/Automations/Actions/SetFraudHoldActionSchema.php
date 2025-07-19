<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\SetFraudHoldAction;
use LaravelJsonApi\Eloquent\Fields\Boolean;

class SetFraudHoldActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = SetFraudHoldAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Boolean::make('on_hold', 'flag_value'),
        ]);
    }
}
