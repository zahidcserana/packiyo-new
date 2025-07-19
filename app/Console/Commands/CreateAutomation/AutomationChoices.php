<?php

namespace App\Console\Commands\CreateAutomation;

use App\Models\Automations\AppliesToCustomers;
use App\Models\Customer;
use Illuminate\Support\Collection;
use LogicException;

class AutomationChoices
{
    private Customer $ownerCustomer;
    private Collection $events;
    private AppliesToCustomers|null $appliesTo;
    private Collection|null $targetCustomers;

    public function __construct(
        Customer $ownerCustomer, Collection $events, ?AppliesToCustomers $appliesTo = null, ?Collection $targetCustomers = null
    )
    {
        $this->ownerCustomer = $ownerCustomer;
        $this->appliesTo = $appliesTo;
        $this->targetCustomers = $targetCustomers;
        $this->events = $events;
    }

    public function getOwnerCustomer(): Customer
    {
        return $this->ownerCustomer;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function getEventNames(): array
    {
        return $this->events->map(fn (string|array $event) => is_array($event) ? $event[0] : $event)->toArray();
    }

    public function getAppliesTo(): AppliesToCustomers|null
    {
        return $this->appliesTo;
    }

    public function getTargetCustomers(): Collection|null
    {
        return $this->targetCustomers;
    }

    public function appliesToMany(): bool
    {
        if ($this->ownerCustomer->isStandalone()) {
            return false;
        } elseif ($this->ownerCustomer->is3pl()) {
            return is_null($this->targetCustomers) || $this->targetCustomers->count() != 1;
        } else {
            throw new LogicException('3PL clients cannot own automations.');
        }
    }
}
