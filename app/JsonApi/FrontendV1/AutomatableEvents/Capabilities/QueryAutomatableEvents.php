<?php

namespace App\JsonApi\FrontendV1\AutomatableEvents\Capabilities;

use App\Components\AutomationComponent;
use LaravelJsonApi\NonEloquent\Capabilities\QueryAll;

class QueryAutomatableEvents extends QueryAll
{
    /**
     * @inheritDoc
     */
    public function get(): iterable
    {
        return \App::make(AutomationComponent::class)->events->all();
    }
}
