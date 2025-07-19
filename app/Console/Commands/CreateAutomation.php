<?php

namespace App\Console\Commands;

use App\Components\AutomationComponent;
use App\Console\Commands\CreateAutomation\AddsAddLineItemAction;
use App\Console\Commands\CreateAutomation\AddsAddPackingNoteAction;
use App\Console\Commands\CreateAutomation\AddsAddTagsAction;
use App\Console\Commands\CreateAutomation\AddsCancelOrderAction;
use App\Console\Commands\CreateAutomation\AddsChargeAdHocRateAction;
use App\Console\Commands\CreateAutomation\AddsChargeShippingBoxRateAction;
use App\Console\Commands\CreateAutomation\AddsMarkAsFulfilledAction;
use App\Console\Commands\CreateAutomation\AddsOrderAgedEventCondition;
use App\Console\Commands\CreateAutomation\AddsOrderFlagCondition;
use App\Console\Commands\CreateAutomation\AddsOrderIsManualCondition;
use App\Console\Commands\CreateAutomation\AddsOrderLineItemsCondition;
use App\Console\Commands\CreateAutomation\AddsOrderItemTagsCondition;
use App\Console\Commands\CreateAutomation\AddsOrderLineItemCondition;
use App\Console\Commands\CreateAutomation\AddsOrderNumberFieldCondition;
use App\Console\Commands\CreateAutomation\AddsOrderTagsCondition;
use App\Console\Commands\CreateAutomation\AddsOrderTextFieldCondition;
use App\Console\Commands\CreateAutomation\AddsOrderTextPatternCondition;
use App\Console\Commands\CreateAutomation\AddsOrderWeightCondition;
use App\Console\Commands\CreateAutomation\AddsEventCustomerTypeCondition;
use App\Console\Commands\CreateAutomation\AddOrderEventSourceCondition;
use App\Console\Commands\CreateAutomation\AddsSetDateFieldAction;
use App\Console\Commands\CreateAutomation\AddsSetDeliveryConfirmationAction;
use App\Console\Commands\CreateAutomation\AddsSetFlagAction;
use App\Console\Commands\CreateAutomation\AddsSetShippingBoxAction;
use App\Console\Commands\CreateAutomation\AddsSetShippingMethodAction;
use App\Console\Commands\CreateAutomation\AddsSetTextFieldAction;
use App\Console\Commands\CreateAutomation\AddsSetWarehouseAction;
use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Console\Commands\CreateAutomation\GetsOrCreatesTags;
use App\Console\Commands\CreateAutomation\OutputsAutomationTable;
use App\Interfaces\AutomationActionInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\Automation;
use App\Models\Automations\AppliesToCustomers;
use App\Models\Automations\OrderAutomation;
use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateAutomation extends Command
{
    use AddsOrderAgedEventCondition,
        AddsEventCustomerTypeCondition,
        AddOrderEventSourceCondition;
    use AddsOrderTextFieldCondition,
        AddsOrderItemTagsCondition,
        AddsOrderFlagCondition,
        AddsOrderNumberFieldCondition,
        AddsOrderWeightCondition,
        AddsOrderLineItemCondition,
        AddsOrderLineItemsCondition,
        AddsOrderIsManualCondition,
        AddsOrderTextPatternCondition,
        AddsOrderTagsCondition;
    use AddsSetFlagAction,
        AddsSetShippingMethodAction,
        AddsAddTagsAction,
        AddsAddLineItemAction,
        AddsSetShippingBoxAction,
        AddsMarkAsFulfilledAction,
        AddsCancelOrderAction,
        AddsAddPackingNoteAction,
        AddsSetTextFieldAction,
        AddsSetDateFieldAction,
        AddsSetDeliveryConfirmationAction,
        AddsChargeAdHocRateAction,
        AddsChargeShippingBoxRateAction,
        AddsSetWarehouseAction;
    use OutputsAutomationTable, NamesAutomations, GetsOrCreatesTags;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an automation.';

    protected AutomationComponent $automationComponent;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AutomationComponent $automationComponent)
    {
        parent::__construct();
        $this->automationComponent = $automationComponent;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // 3PL or standalone customer.
        $chosenOwnerCustomer = $this->getChosenOwnerCustomer(self::getOwnerCustomerChoices());
        $appliesToChoice = null;
        $chosenTargetCustomers = null;

        if ($chosenOwnerCustomer->is3pl()) {
            $appliesToChoice = $this->getAppliesToChoice();

            if (AppliesToCustomers::isNotAll($appliesToChoice)) {
                $chosenTargetCustomers = $this->getChosenTargetCustomers(
                    self::getTargetCustomerChoices($chosenOwnerCustomer), $appliesToChoice
                );
            }
        }

        $chosenEvents = $this->getChosenEvents($this->getEventChoices());
        $automationChoices = new AutomationChoices($chosenOwnerCustomer, $chosenEvents, $appliesToChoice, $chosenTargetCustomers);
        $addedConditions = $this->getAddedConditions($automationChoices);
        $addedActions = $this->getAddedActions($automationChoices);
        $this->automationTableFromChoices($automationChoices, $addedConditions, $addedActions);
        $this->createAutomation($automationChoices, $addedConditions, $addedActions);

        return 0;
    }

    protected function getChosenOwnerCustomer(array $customerChoices): Customer
    {
        $customerName = $this->anticipate(
            __('Which customer should own the automation?'),
            $customerChoices,
            reset($customerChoices)
        );

        return Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
    }

    protected static function getOwnerCustomerChoices(): array
    {
        return Customer::with(['contactInformation'])
            ->whereNull('parent_id')
            ->has('contactInformation')
            ->get()
            ->mapWithKeys(fn (Customer $customer) => [$customer->id => $customer->contactInformation->name])
            ->toArray();
    }

    protected function getAppliesToChoice(): AppliesToCustomers
    {
        return AppliesToCustomers::from($this->choice(
            __('Which clients should the automation apply to?'),
            collect(AppliesToCustomers::cases())
                ->filter(fn (AppliesToCustomers $appliesTo) => $appliesTo != AppliesToCustomers::OWNER)
                ->pluck('value')
                ->toArray()
        ));
    }

    protected static function getTargetCustomerChoices(Customer $customer): Collection
    {
        return $customer->children()
            ->getQuery()
            ->with(['contactInformation'])
            ->has('contactInformation')
            ->get()
            ->mapWithKeys(fn (Customer $customer) => [$customer->id => $customer->contactInformation->name]);
    }

    protected function getChosenTargetCustomers(Collection $customerChoices, AppliesToCustomers $appliesTo): EloquentCollection
    {
        $customerNames = array_unique($this->choice(
            $appliesTo == AppliesToCustomers::NOT_SOME
                ? __('Which ones should be excluded? (Case-sensitive, comma-separated, tab to auto-complete.)')
                : __('Which ones should be included? (Case-sensitive, comma-separated, tab to auto-complete.)'),
            $customerChoices->toArray(),
            null, // Default
            null, // Attempts
            true // Multiple
        ));

        return Customer::whereHas('contactInformation', function (Builder $query) use (&$customerNames) {
            $query->whereIn('name', $customerNames);
        })->get();
    }

    protected static function getEventChoices(): array
    {
        return collect(OrderAutomation::getSupportedEvents()) // TODO: Make dynamic.
            ->map(fn (string $event) => str_replace('\\\\', '\\', $event))
            ->toArray();
    }

    protected function getChosenEvents(array $eventChoices): Collection
    {
        do {
            $events[] = $this->addEvent($eventChoices);
        } while ($this->confirm(__('Do you want to add another event?'), false));

        return collect($events);
    }

    protected function addEvent(array $eventChoices): string|array
    {
        $chosenEvent = $this->choice(__('Which event should trigger the automation?'), $eventChoices);
        $methodName = $this->getAddMethodByClassName($chosenEvent, 'Condition');

        if (method_exists($this, $methodName)) {
            $chosenEvent = [$chosenEvent, call_user_func([$this, $methodName])];
        }

        return $chosenEvent;
    }

    protected function getAddedConditions(AutomationChoices $automationChoices): Collection
    {
        $conditions = [];
        $firstLoop = true;

        if ($this->confirm(__('Do you want to add a condition criteria?'), true)) {
            do {
                $conditions[] = $this->addCondition($automationChoices, !$firstLoop);
                $firstLoop = false;
            } while ($this->confirm(__('Do you want to add another condition criteria?'), false));
        }

        return collect($conditions);
    }

    protected function addCondition(AutomationChoices $automationChoices, bool $chooseLogicalOperator): AutomationConditionInterface|array
    {
        $isAlternative = $chooseLogicalOperator ? $this->getIsAlternative() : false;
        $chosenCondition = $this->choice(
            __('Which condition do you want to add?'),
            $this->automationComponent->getConditions(
                    $automationChoices->getEventNames(), $automationChoices->appliesToMany()
                )
                ->map(fn (AutomationConditionInterface $condition) => $condition::class)
                ->toArray()
        );

        $condition = call_user_func([$this, $this->getAddMethodByClassName($chosenCondition)], $automationChoices);
        $this->setIsAlternative($condition, $isAlternative);

        return $condition;
    }

    protected function getIsAlternative(): bool
    {
        $isAlternative = null;
        $logicalOperator = $this->choice(
            __('Which logical operator should link to the previous condition?'),
            [__('and'), __('or')],
            __('and')
        );

        if ($logicalOperator == __('and')) {
            $isAlternative = false;
        } elseif ($logicalOperator == __('or')) {
            $isAlternative = true;
        } else {
            throw new RuntimeException('Invalid flag value given.');
        }

        return $isAlternative;
    }

    protected function setIsAlternative(AutomationConditionInterface|array &$condition, bool $isAlternative): void
    {
        if (is_array($condition)) {
            $condition[0]->is_alternative = $isAlternative;
        } else {
            $condition->is_alternative = $isAlternative;
        }
    }

    protected function getAddedActions(AutomationChoices $automationChoices): Collection
    {
        do {
            $actions[] = $this->addAction($automationChoices);
        } while ($this->confirm(__('Do you want to add another action?'), false));

        return collect($actions);
    }

    protected function addAction(AutomationChoices $automationChoices): AutomationActionInterface|array
    {
        $chosenAction = $this->choice(
            __('Which action do you want to add?'),
            $this->automationComponent->getActions(
                    $automationChoices->getEventNames(), $automationChoices->appliesToMany()
                )
                ->map(fn (AutomationActionInterface $action) => $action::class)
                ->toArray()
        );

        return call_user_func([$this, $this->getAddMethodByClassName($chosenAction)], $automationChoices);
    }

    protected function getAddMethodByClassName(string $chosenAction, string $suffix = '')
    {
        return 'add' . class_basename($chosenAction) . $suffix;
    }

    protected function createAutomation(
        AutomationChoices $automationChoices, Collection $addedConditions, Collection $addedActions
    ): void
    {
        if ($this->confirm(__('Do you want to save your automation?'), true)) {
            $chosenOwnerCustomer = $automationChoices->getOwnerCustomer();

            DB::transaction(fn () => Automation::buildFromCommand(
                OrderAutomation::class, // TODO: Get from input.
                $chosenOwnerCustomer,
                $automationChoices->getEvents(),
                $this->getGivenName($chosenOwnerCustomer),
                $this->confirm(__('Do you want the automation to be enabled?')),
                $addedConditions,
                $addedActions,
                appliesTo: $automationChoices->getAppliesTo(),
                chosenTargetCustomers: $automationChoices->getTargetCustomers()
            ), 2);

            $this->line('Your automation was saved.');
        } else {
            $this->line('Exited without saving.');
        }
    }
}
