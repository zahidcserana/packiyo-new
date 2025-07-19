<?php

namespace App\JsonApi\FrontendV1\AutomatableOperations;

use App\Models\Automations\IdentifiesUsingSlugs;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class AutomatableOperationResource extends JsonApiResource
{
    use IdentifiesUsingSlugs;

    public function id(): string
    {
        return static::classToSlug($this->resource->type::getOperationClass());
    }

    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        $class = $this->resource->type;

        return [
            'type' => $class::getOperationClass()
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('supported_events')->withData($this->resource->supportedEvents())
        ];
    }
}
