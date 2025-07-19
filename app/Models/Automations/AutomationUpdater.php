<?php

namespace App\Models\Automations;

use App\Exceptions\AutomationException;
use App\Interfaces\AutomationActionInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\Automation;
use App\Models\Customer;

class AutomationUpdater
{
    protected Automation $currentRevision;
    protected Automation $updatedRevision;

    public function __construct(Automation $automation)
    {
        $this->currentRevision = $automation;
        $this->updatedRevision = static::cloneAndDelete($automation);
    }

    protected static function cloneAndDelete(Automation $automation): Automation
    {
        $clone = new Automation($automation->toArray());
        $clone->customer()->associate($automation->customer);
        $clone->previousRevision()->associate($automation);
        $automation->name = $automation->name . ' ' . $automation->created_at;
        $automation->is_enabled = false;
        $automation->save();
        $automation->delete();
        $clone->save();
        $clone->revisions()->attach($clone->id);

        $cloneConditionrOrAction = fn (AutomationConditionInterface|AutomationActionInterface $conditionOrAction)
            => static::cloneConditionrOrAction($clone, $conditionOrAction);
        $automation->conditions->map($cloneConditionrOrAction);
        $automation->actions->map($cloneConditionrOrAction);

        if (AppliesToCustomers::isNotAll($automation->applies_to)) {
            $automation->appliesToCustomers->map(fn (Customer $customer)
                => $clone->appliesToCustomers()->attach($customer->id));
        }

        return $clone;
    }

    protected static function cloneConditionrOrAction(
        Automation $automation,
        AutomationConditionInterface|AutomationActionInterface|array $conditionOrAction
    ): void {
        $clone = new ($conditionOrAction::class)($conditionOrAction->toArray());
        $clone->position = $conditionOrAction->position;
        $clone->id = null;
        $clone->automation()->associate($automation)->save();
        $conditionOrAction->relationshipsForClone($clone);
    }

    public function addCustomer(Customer $customer): static
    {
        $this->updatedRevision->validateCustomer($customer);
        $this->updatedRevision->appliesToCustomers()->attach($customer->id);

        return $this;
    }

    public function removeCustomer(Customer $customer): static
    {
        if (!$this->updatedRevision->appliesToCustomers->contains($customer)) {
            throw new AutomationException('The automation does not apply to the given customer.');
        }

        $this->updatedRevision->appliesToCustomers()->detach($customer->id);

        return $this;
    }

    /**
     * Returns the subtype instance.
     */
    public function get(): Automation
    {
        return Automation::find($this->updatedRevision->id);
    }
}
