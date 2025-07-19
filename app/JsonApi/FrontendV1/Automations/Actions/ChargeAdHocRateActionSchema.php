<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\ChargeAdHocRateAction;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\Number;

class ChargeAdHocRateActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = ChargeAdHocRateAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Number::make('position'),
            Number::make('minimum'),
            Number::make('tolerance'),
            Number::make('threshold'),
        ]);
    }
}
