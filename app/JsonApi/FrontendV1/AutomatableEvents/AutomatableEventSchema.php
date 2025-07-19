<?php

namespace App\JsonApi\FrontendV1\AutomatableEvents;

use App\Models\Automations\AutomatableEvent;
use LaravelJsonApi\Core\Schema\Schema;
use LaravelJsonApi\NonEloquent\Fields\Attribute;
use LaravelJsonApi\NonEloquent\Fields\ID;
use LaravelJsonApi\NonEloquent\Filters\Filter;

class AutomatableEventSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = AutomatableEvent::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make()->matchAs('[a-z]+'),
            Attribute::make('name'),
            Attribute::make('title'),
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

    public function repository(): AutomatableEventRepository
    {
        return AutomatableEventRepository::make()
            ->withServer($this->server)
            ->withSchema($this);
    }
}
