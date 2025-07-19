<?php

namespace App\Listeners;

use App\Components\AutomationComponent;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\ConditionalAutomatableEvent;
use App\Models\Automations\ActsAsAutomationUser;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomationListener
{
    use ActsAsAutomationUser;

    protected AutomationComponent $automationComponent;

    public function __construct(AutomationComponent $automationComponent)
    {
        $this->automationComponent = $automationComponent;
    }

    public function handle(AutomatableEvent $event)
    {
        $customer = $event->getOperation()->customer;

        if ($customer->is3plChild()) {
            $customer = $customer->parent;
        }

        try {
            $this->actingAsAutomation(
                fn () => $event instanceof ConditionalAutomatableEvent
                    ? $event->runAutomationOnSelf()
                    : $this->automationComponent->run($event)
            );
        } catch (Throwable $e) {
            $this->warnOfError($e);
        }
    }

    protected function warnOfError(Throwable $e) {
        Log::warning("Copilot automation error in {$e->getFile()}:{$e->getLine()}: " . $e->getMessage());
        Log::warning("Stack trace:\n" . $e->getTraceAsString());
    }
}
