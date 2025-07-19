<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\SetPackingDimensionsAction;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Str;


class SetPackingDimensionsActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = SetPackingDimensionsAction::class;
    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            HasOne::make('shipping-box')
        ]);
    }
}
