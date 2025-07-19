<?php

namespace App\Components;

use App\Components\Automation\AutomatableEventProvider;
use App\Components\Automation\AutomationConditioner;
use App\Components\Automation\AutomationRunner;
use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomatableOperation;
use App\Models\Automation;
use App\Models\AutomationEventCondition;
use App\Models\Automations\AppliesToCustomers;
use App\Components\Automation\AutomatableOperationProvider;
use App\Components\Automation\AutomationActionTypeProvider;
use App\Components\Automation\AutomationConditionTypeProvider;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomationComponent
{
    use AccessesAttributes;

    protected AutomatableOperationProvider $operationProvider;
    protected AutomatableEventProvider $eventProvider;
    protected AutomationConditionTypeProvider $conditionProvider;
    protected AutomationActionTypeProvider $actionProvider;
    protected AutomationConditioner $conditioner;
    protected AutomationRunner $runner;

    public function __construct(
        AutomatableOperationProvider    $operationProvider,
        AutomatableEventProvider        $eventProvider,
        AutomationConditionTypeProvider $conditionProvider,
        AutomationActionTypeProvider    $actionProvider,
        AutomationConditioner           $conditioner,
        AutomationRunner                $runner
    )
    {
        $this->operationProvider = $operationProvider;
        $this->eventProvider = $eventProvider;
        $this->conditionProvider = $conditionProvider;
        $this->actionProvider = $actionProvider;
        $this->conditioner = $conditioner;
        $this->runner = $runner;
    }

    protected function getOperationsAttribute(): AutomatableOperationProvider
    {
        return $this->operationProvider;
    }

    protected function getEventsAttribute(): AutomatableEventProvider
    {
        return $this->eventProvider;
    }

    protected function getConditionTypesAttribute(): AutomationConditionTypeProvider
    {
        return $this->conditionProvider;
    }

    protected function getActionTypesAttribute(): AutomationActionTypeProvider
    {
        return $this->actionProvider;
    }

    public function getConditions(array $events = null, bool $forMany = false): Collection
    {
        return $this->conditioner->getConditions($events, $forMany);
    }

    public function getActions(array $events = null, bool $forMany = false): Collection
    {
        return $this->runner->getActions($events, $forMany);
    }

    public function run(AutomatableEvent $event): void
    {
        $operation = $event->getOperation();
        $operation->refresh(); // Because of how OrderComponent::store works.

        $automations = $operation->customer->is3plChild()
            ? $this->getAutomationsFor3plChild($event, $operation)
            : $this->getAutomationsForOwner($event, $operation);

        $automations->each(fn (Automation $automation) => $automation->run($event));
    }

    protected function getAutomationsFor3plChild(
        AutomatableEvent $event,
        AutomatableOperation $operation
    ): EloquentCollection {
        return Automation::where([
                'customer_id' => $operation->customer->parent_id,
                'is_enabled' => true
            ])
            ->whereNull('group_action_id')
            ->where(function (Builder $query) use (&$operation) {
                $query->where('applies_to', AppliesToCustomers::ALL)
                    ->orWhere(function (Builder $query) use (&$operation) {
                        $query->where('applies_to', AppliesToCustomers::SOME)
                            ->whereHas('appliesToCustomers', function (Builder $query) use (&$operation) {
                                $query->where('customer_id', $operation->customer_id);
                            });
                    })
                    ->orWhere(function (Builder $query) use (&$operation) {
                        $query->where('applies_to', AppliesToCustomers::NOT_SOME)
                            ->whereDoesntHave('appliesToCustomers', function (Builder $query) use (&$operation) {
                                $query->where('customer_id', $operation->customer_id);
                            });
                    });
            })
            ->whereJsonContains('target_events', $event::class)
            ->orderBy('position')
            ->get();
    }

    protected function getAutomationsForOwner(
        AutomatableEvent $event,
        AutomatableOperation $operation
    ): EloquentCollection
    {
        return Automation::where([
                'customer_id' => $operation->customer_id,
                'is_enabled' => true,
                'applies_to' => AppliesToCustomers::OWNER
            ])
            ->whereNull('group_action_id')
            ->whereJsonContains('target_events', $event::class)
            ->orderBy('position')
            ->get();
    }

    public function runTimedAutomations(): void
    {
        $conditions = AutomationEventCondition::whereHas(
            'automation', fn (Builder $query) => $query->where('is_enabled', true)
        )->get();

        foreach ($conditions as $condition) {
            $query = $condition->findOperations();
            $query->whereNotIn(
                    'id',
                    $condition->excludedOperations()->select([$query->from . '.id'])
                )
                ->orderBy('id')
                ->each(fn (AutomatableOperation $operation)
                    => event($condition->getEvent($operation)));
        }
    }

    /**
     * @throws AutomationException
     */
    public function enable(Automation $automation): void
    {
        try {
            DB::transaction(function() use ($automation) {
                $automation->enable();
            });
        } catch (Throwable $e) {
            $this->LogAndThrowException($automation, $e, 'Enable Automation');
        }
    }

    /**
     * @throws AutomationException
     */
    public function disable(Automation $automation): void
    {
        try {
            DB::transaction(function() use ($automation) {
                $automation->disable();
            });
        } catch (Throwable $e) {
            $this->LogAndThrowException($automation, $e, 'Disable Automation');
        }
    }

    /**
     * @throws AutomationException
     */
    public function detachCustomersByIds(Automation $automation, array $customerIds): void
    {
        try {
            DB::transaction(function() use ($automation, $customerIds) {
                $automation->appliesToCustomers()->detach($customerIds);
            });
        } catch (Throwable $e) {
            $this->LogAndThrowException($automation, $e, 'Detach Customers to the Automation');
        }
    }

    /**
     * @throws AutomationException
     * @throws Throwable
     */
    public function attachCustomersByIds(Automation $automation, array $customerIds, $useTransaction = true): void
    {
        try {
            $customers = Customer::leftJoin('automation_applies_to_customer as aatc',
                function ($join) use ($automation) {
                    $join->on('aatc.customer_id', 'customers.id')
                        ->where('aatc.automation_id', $automation->id);
                })
                ->whereIn('customers.id', $customerIds)
                ->whereNull('aatc.id')
                ->select('customers.*')
                ->get();

            if ($useTransaction) {
                DB::beginTransaction();
            }

            foreach ($customers as $customer) {
                Automation::addCustomer($automation, $customer);
            }
            if ($useTransaction) {
                DB::commit();
            }
        } catch (Throwable $e) {
            if ($useTransaction) {
                DB::rollBack();
            }
            $this->LogAndThrowException($automation, $e, 'Attach Customers to the Automation');
        }
    }

    /**
     * @throws Throwable
     * @throws AutomationException
     */
    public function syncCustomersByIds(Automation $automation, array $customerIds): void
    {
        $itemsToRemove = $automation->appliesToCustomers->filter(function ($item) use ($customerIds)
        {
            return !in_array($item->id,$customerIds);
        });

        try {
            DB::transaction(function() use ($automation, $itemsToRemove, $customerIds) {
                $this->attachCustomersByIds($automation, $customerIds, false);

                $automation->appliesToCustomers()->detach($itemsToRemove->pluck('id'));
            });
        } catch (Throwable $e) {
            $this->LogAndThrowException($automation, $e, 'Sync Automation Customers');
        }
    }

    /**
     * @throws AutomationException
     */
    private function LogAndThrowException(Automation $automation, Throwable $throwable, string $routine): void
    {
        $message = 'Unable to '.$routine.'. ID: '.$automation->id . ' - ' . $throwable->getMessage();
        Log::error($message, [
            'exception' => $throwable,
            'error' => $throwable->getMessage(),
            'trace' => $throwable->getTrace()
        ]);
        throw new AutomationException($message);
    }
}

