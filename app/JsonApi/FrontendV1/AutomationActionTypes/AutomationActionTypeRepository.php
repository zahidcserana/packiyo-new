<?php

namespace App\JsonApi\FrontendV1\AutomationActionTypes;

use App\Components\Automation\AutomationActionTypeProvider;
use LaravelJsonApi\Contracts\Store\QueriesAll;
use LaravelJsonApi\NonEloquent\AbstractRepository;

class AutomationActionTypeRepository extends AbstractRepository implements QueriesAll
{
    protected AutomationActionTypeProvider $provider;

    public function __construct(AutomationActionTypeProvider $provider)
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
    public function queryAll(): Capabilities\QueryAutomationActionTypes
    {
        return Capabilities\QueryAutomationActionTypes::make()
            ->withServer($this->server)
            ->withSchema($this->schema);
    }
}
