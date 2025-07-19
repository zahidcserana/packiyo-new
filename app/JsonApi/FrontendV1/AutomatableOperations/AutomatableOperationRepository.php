<?php

namespace App\JsonApi\FrontendV1\AutomatableOperations;

use App\Components\Automation\AutomatableOperationProvider;
use App\Models\Automations\IdentifiesUsingSlugs;
use LaravelJsonApi\Contracts\Store\QueriesAll;
use LaravelJsonApi\NonEloquent\AbstractRepository;

class AutomatableOperationRepository extends AbstractRepository implements QueriesAll
{
    use IdentifiesUsingSlugs;

    protected AutomatableOperationProvider $provider;

    public function __construct(AutomatableOperationProvider $provider)
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
    public function queryAll(): Capabilities\QueryAutomatableOperations
    {
        return Capabilities\QueryAutomatableOperations::make()
            ->withServer($this->server)
            ->withSchema($this->schema);
    }
}
