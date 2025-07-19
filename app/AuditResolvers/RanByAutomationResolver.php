<?php

namespace App\AuditResolvers;

use App\Components\Automation\AutomationContext;
use Illuminate\Support\Facades\App;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

class RanByAutomationResolver implements Resolver
{
    public static function resolve(Auditable $auditable)
    {
        $automationInContext = App::make(AutomationContext::class)->currentAutomation();

        if ($automationInContext) {
            return $automationInContext->id;
        }

        return null;
    }
}
