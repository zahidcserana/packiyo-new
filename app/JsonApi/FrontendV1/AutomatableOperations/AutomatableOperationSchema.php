<?php

namespace App\JsonApi\FrontendV1\AutomatableOperations;

use App\Models\Automations\AutomatableOperation;
use LaravelJsonApi\Core\Schema\Schema;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\NonEloquent\Fields\Attribute;
use LaravelJsonApi\NonEloquent\Fields\ID;
use LaravelJsonApi\NonEloquent\Filters\Filter;

class AutomatableOperationSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = AutomatableOperation::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make()->matchAs('[a-z]+'),
            Attribute::make('type'),
            HasMany::make('supported_events')->type('automatable-events')
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            Filter::make('slugs')
        ];
    }

    public function repository(): AutomatableOperationRepository
    {
        return AutomatableOperationRepository::make()
            ->withServer($this->server)
            ->withSchema($this);
    }
}
