<?php

namespace App\JsonApi\FrontendV1\AutomationActionTypes\Capabilities;

use App\Components\AutomationComponent;
use LaravelJsonApi\NonEloquent\Capabilities\QueryAll;

class QueryAutomationActionTypes extends QueryAll
{
    /**
     * @inheritDoc
     */
    public function get(): iterable
    {
        return \App::make(AutomationComponent::class)->actionTypes->all();
    }
}
