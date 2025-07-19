<?php

namespace App\JsonApi\FrontendV1\AutomationConditionTypes;

use App\Components\Automation\AutomationConditionTypeProvider;
use LaravelJsonApi\Contracts\Store\QueriesAll;
use LaravelJsonApi\NonEloquent\AbstractRepository;
use App\JsonApi\FrontendV1\AutomationConditionTypes\Capabilities\QueryAutomationConditionTypes;

class AutomationConditionTypeRepository extends AbstractRepository implements QueriesAll
{
    protected AutomationConditionTypeProvider $provider;

    public function __construct(AutomationConditionTypeProvider $provider)
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
    public function queryAll(): QueryAutomationConditionTypes
    {
        return QueryAutomationConditionTypes::make()
            ->withServer($this->server)
            ->withSchema($this->schema);
    }
}
