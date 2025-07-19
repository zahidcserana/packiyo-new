<?php

namespace App\JsonApi\FrontendV1\AutomatableOperations\Capabilities;

use App\Components\AutomationComponent;
use LaravelJsonApi\NonEloquent\Capabilities\QueryAll;

class QueryAutomatableOperations extends QueryAll
{
    /**
     * @inheritDoc
     */
    public function get(): iterable
    {
        return \App::make(AutomationComponent::class)->operations->all();
    }
}
