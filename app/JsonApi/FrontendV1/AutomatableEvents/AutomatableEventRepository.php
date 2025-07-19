<?php

namespace App\JsonApi\FrontendV1\AutomatableEvents;

use App\Components\Automation\AutomatableEventProvider;
use LaravelJsonApi\Contracts\Store\QueriesAll;
use LaravelJsonApi\NonEloquent\AbstractRepository;

class AutomatableEventRepository extends AbstractRepository implements QueriesAll
{
    protected AutomatableEventProvider $provider;

    public function __construct(AutomatableEventProvider $provider)
    {
        $this->provider = $provider;
    }

    public function find(string $resourceId): ?object
    {
        return $this->provider->get($resourceId);
    }

    /**
     * @inheritDoc
     */
    public function queryAll(): Capabilities\QueryAutomatableEvents
    {
        return Capabilities\QueryAutomatableEvents::make()
            ->withServer($this->server)
            ->withSchema($this->schema);
    }
}
