<?php

use App\Events\OrderCreatedEvent;
use App\Models\{
    Automation,
    AutomationAction,
    Automations\OrderAutomation,
    Customer
};
use App\Models\AutomationActions\RunFirstMatchingOfGroupAction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Behat steps to test automation steps.
 */
trait AutomationGroupSteps
{
    protected AutomationAction|null $actionInScope = null;

    /**
     * @Given an automation group named :automationGroupName owned by :customerName is enabled
     */
    public function anAutomationGroupNamedOwnedByIsEnabled(string $automationGroupName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope = OrderAutomation::factory()->create([
            'customer_id' => $customer->id,
            'name' => $automationGroupName,
            'is_enabled' => true,
            'target_events' => [OrderCreatedEvent::class]
        ]);
        $this->actionInScope = RunFirstMatchingOfGroupAction::factory()
            ->create(['automation_id' => $this->automationInScope->id]);
    }

    /**
     * @Given the automation group includes the automation :automationName
     */
    public function theAutomationGroupIncludesTheAutomation(string $automationName): void
    {
        $automation = Automation::where([
            'group_action_id' => $this->actionInScope->customer_id,
            'name' => $automationName
        ])->firstOrFail();
        $automation->groupAction()->associate($this->actionInScope);
        $automation->save();
    }
}
