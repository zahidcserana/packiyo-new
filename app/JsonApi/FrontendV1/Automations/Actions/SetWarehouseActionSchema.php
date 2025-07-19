<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\SetWarehouseAction;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;

class SetWarehouseActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = SetWarehouseAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            Map::make('warehouse', [
                Number::make('id'),
                Str::make('name'),
            ])->on('warehouse'),
        ]);
    }
}
