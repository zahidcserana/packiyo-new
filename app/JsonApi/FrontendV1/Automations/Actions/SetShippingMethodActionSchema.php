<?php

namespace App\JsonApi\FrontendV1\Automations\Actions;

use App\Models\AutomationActions\SetShippingMethodAction;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;

class SetShippingMethodActionSchema extends AutomationActionSchema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = SetShippingMethodAction::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            HasOne::make('shipping_method')
        ]);
    }
}
