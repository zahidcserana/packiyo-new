<?php

namespace App\JsonApi\FrontendV1\AutomationConditionTypes\Capabilities;

use App\Components\AutomationComponent;
use LaravelJsonApi\NonEloquent\Capabilities\QueryAll;

class QueryAutomationConditionTypes extends QueryAll
{
    /**
     * @inheritDoc
     */
    public function get(): iterable
    {
        return \App::make(AutomationComponent::class)->conditionTypes->all();
    }
}
