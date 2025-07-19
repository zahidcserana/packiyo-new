<?php

namespace App\Components\Automation;

use App\Models\Automation;

class AutomationContext
{
    private ?Automation $currentAutomation = null;

    public function currentAutomation(): ?Automation
    {
        return $this->currentAutomation;
    }

    private function setCurrentAutomation(Automation $automation): void
    {
        $this->currentAutomation = $automation;
    }

    private function clear(): void
    {
        $this->currentAutomation = null;
    }

    public function run(Automation $automation, callable $callback): void
    {
        $this->setCurrentAutomation($automation);
        $callback();
        $this->clear();
    }
}
