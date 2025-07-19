<?php

namespace App\JsonApi\FrontendV1\AutomationActionTypes;

use App\Models\Automations\IdentifiesUsingSlugs;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class AutomationActionTypeResource extends JsonApiResource
{
    use IdentifiesUsingSlugs;

    public function id(): string
    {
        $class = $this->resource->type;

        return static::classToSlug($class);
    }

    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'name' => class_basename($this->resource->type),
            'title' => class_basename($this->resource->title)
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
        return [];
    }
}
