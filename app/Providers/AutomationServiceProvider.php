<?php

namespace App\Providers;

use App\Components\Automation\AutomationContext;
use App\Components\Automation\AutomatableEventProvider;
use App\Components\Automation\AutomatableOperationProvider;
use App\Components\Automation\AutomationActionTypeProvider;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Models\AutomationAction;
use App\Models\AutomationActions\AddLineItemAction;
use App\Models\AutomationActions\AddPackingNoteAction;
use App\Models\AutomationActions\AddTagsAction;
use App\Models\AutomationActions\CancelOrderAction;
use App\Models\AutomationActions\ChargeAdHocRateAction;
use App\Models\AutomationActions\ChargeShippingBoxRateAction;
use App\Models\AutomationActions\MarkAsFulfilledAction;
use App\Models\AutomationActions\RunFirstMatchingOfGroupAction;
use App\Models\AutomationActions\SetDateFieldAction;
use App\Models\AutomationActions\SetDeliveryConfirmationAction;
use App\Models\AutomationActions\SetFlagAction;
use App\Models\AutomationActions\SetPackingDimensionsAction;
use App\Models\AutomationActions\SetShippingBoxAction;
use App\Models\AutomationActions\SetShippingMethodAction;
use App\Models\AutomationActions\SetTextFieldAction;
use App\Models\AutomationConditions\EventCustomerTypeCondition;
use App\Models\AutomationConditions\OrderChannelCondition;
use App\Models\AutomationConditions\OrderEventSourceCondition;
use App\Models\AutomationConditions\OrderFlagCondition;
use App\Models\AutomationConditions\OrderIsManualCondition;
use App\Models\AutomationConditions\OrderItemTagsCondition;
use App\Models\AutomationConditions\OrderLineItemCondition;
use App\Models\AutomationConditions\OrderLineItemsCondition;
use App\Models\AutomationConditions\OrderNumberFieldCondition;
use App\Models\AutomationConditions\OrderTagsCondition;
use App\Models\AutomationConditions\OrderTextFieldCondition;
use App\Models\AutomationConditions\OrderTextPatternCondition;
use App\Models\AutomationConditions\OrderWeightCondition;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use App\Components\AutomationComponent;
use App\Components\Automation\AutomationConditioner;
use App\Components\Automation\AutomationRunner;
use App\Components\Automation\AutomationConditionTypeProvider;
use App\Interfaces\AutomatableEvent as AutomatableEventInterface;
use App\Interfaces\AutomationActionInterface;
use App\Interfaces\AutomationInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AutomatableEvent;
use App\Models\Automations\AutomatableOperation;

class AutomationServiceProvider extends ServiceProvider
{
    const OPERATIONS_DIR = 'Models/Automations';
    const OPERATIONS_NAMESPACE = 'App\\Models\\Automations\\';
    const EVENTS_DIR = 'Events';
    const EVENTS_NAMESPACE = 'App\\Events\\';
    const CONDITIONS_DIR = 'Models/AutomationConditions';
    const CONDITION_NAMESPACE = 'App\\Models\\AutomationConditions\\';
    const ACTIONS_DIR = 'Models/AutomationActions';
    const ACTIONS_NAMESPACE = 'App\\Models\\AutomationActions\\';

    const CONDITION_BASE_MODELS = [
        EventCustomerTypeCondition::class,
        OrderChannelCondition::class,
        OrderEventSourceCondition::class,
        OrderFlagCondition::class,
        OrderIsManualCondition::class,
        OrderItemTagsCondition::class,
        OrderLineItemCondition::class,
        OrderLineItemsCondition::class,
        OrderNumberFieldCondition::class,
        OrderTagsCondition::class,
        OrderTextFieldCondition::class,
        OrderTextPatternCondition::class,
        OrderWeightCondition::class,
    ];

    const ACTION_BASE_MODELS = [
        AddLineItemAction::class,
        AddPackingNoteAction::class,
        AddTagsAction::class,
        CancelOrderAction::class,
        ChargeAdHocRateAction::class,
        ChargeShippingBoxRateAction::class,
        MarkAsFulfilledAction::class,
        RunFirstMatchingOfGroupAction::class,
        SetDateFieldAction::class,
        SetDeliveryConfirmationAction::class,
        SetFlagAction::class,
        SetPackingDimensionsAction::class,
        SetShippingBoxAction::class,
        SetShippingMethodAction::class,
        SetTextFieldAction::class,
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerOperations();
        $this->app->when(AutomatableOperationProvider::class)
            ->needs(AutomatableOperation::class)
            ->giveTagged('automatable-operations');

        $this->registerEvents();
        $this->app->when(AutomatableEventProvider::class)
            ->needs(AutomatableEvent::class)
            ->giveTagged('automatable-events');

        $this->registerConditions();
        $this->app->when(AutomationConditionTypeProvider::class)
            ->needs(AutomationConditionInterface::class)
            ->giveTagged('automation-conditions');
        $this->app->when(AutomationConditioner::class)
            ->needs(AutomationConditionInterface::class)
            ->giveTagged('automation-conditions');

        $this->registerActions();
        $this->app->when(AutomationActionTypeProvider::class)
            ->needs(AutomationActionInterface::class)
            ->giveTagged('automation-actions');
        $this->app->when(AutomationRunner::class)
            ->needs(AutomationActionInterface::class)
            ->giveTagged('automation-actions');

        $this->app->singleton(AutomatableOperationProvider::class);
        $this->app->singleton(AutomatableEventProvider::class);
        $this->app->singleton(AutomationConditionTypeProvider::class);
        $this->app->singleton(AutomationActionTypeProvider::class);
        $this->app->singleton(AutomationConditioner::class);
        $this->app->singleton(AutomationRunner::class);
        $this->app->singleton(AutomationComponent::class);
        $this->app->singleton(AutomationContext::class);
    }

    protected function registerOperations()
    {
        $operationsDir = app_path(self::OPERATIONS_DIR);
        $operationFiles = File::files($operationsDir);
        $operationClasses = [];

        foreach ($operationFiles as $operationFile) {
            $operationClass = self::OPERATIONS_NAMESPACE . pathinfo($operationFile, PATHINFO_FILENAME);

            if (in_array(AutomationInterface::class, class_implements($operationClass))) {
                $operationClasses[] = $operationClass;
                $this->app->bind($operationClass, fn ($app) => new AutomatableOperation($operationClass));
            }
        }

        $this->app->tag($operationClasses, 'automatable-operations');
    }

    protected function registerConditions()
    {
        $conditionsDir = app_path(self::CONDITIONS_DIR);
        $conditionsFiles = File::files($conditionsDir);

        $conditionClasses = array_merge(
            $this->registerConditionFiles($conditionsFiles, false),
            $this->registerConditionFiles($conditionsFiles, true)
        );

        $this->app->tag($conditionClasses, 'automation-conditions');
    }

    private function registerConditionFiles($conditionsFiles, bool $onlyBase): array
    {
        $classes = [];
        foreach ($conditionsFiles as $conditionFile) {
            $conditionClass = self::CONDITION_NAMESPACE . pathinfo($conditionFile, PATHINFO_FILENAME);

            if (!$this->shouldBeRegistered($conditionClass, AutomationConditionInterface::class, $onlyBase)) {
                continue;
            }

            $classes[] = $conditionClass;
            $this->app->bind($conditionClass);
            AutomationCondition::registerChildCallback($conditionClass);
        }

        return $classes;
    }

    private function shouldBeRegistered($className, $classInterface, $onlyBaseModel) : bool
    {
        if (!in_array($classInterface, class_implements($className))) {
            return false;
        }

        return ($onlyBaseModel && in_array(AutomationBaseObjectInterface::class, class_implements($className))) ||
            (!$onlyBaseModel && !in_array(AutomationBaseObjectInterface::class, class_implements($className)));
    }

    protected function registerEvents()
    {
        $eventsDir = app_path(self::EVENTS_DIR);
        $eventFiles = File::files($eventsDir);
        $eventClasses = [];

        foreach ($eventFiles as $eventFile) {
            $eventClass = self::EVENTS_NAMESPACE . pathinfo($eventFile, PATHINFO_FILENAME);

            if (in_array(AutomatableEventInterface::class, class_implements($eventClass))) {
                $eventClasses[] = $eventClass;
                $this->app->bind($eventClass, fn ($app) => new AutomatableEvent($eventClass, $eventClass::getTitle()));
            }
        }

        $this->app->tag($eventClasses, 'automatable-events');
    }

    protected function registerActions()
    {
        $actionsDir = app_path(self::ACTIONS_DIR);
        $actionFiles = File::files($actionsDir);

        $actionClasses = array_merge(
            $this->registerActionFiles($actionFiles, false),
            $this->registerActionFiles($actionFiles, true)
        );

        $this->app->tag($actionClasses, 'automation-actions');
    }

    private function registerActionFiles($actionFiles, bool $onlyBase): array
    {
        $classes = [];

        foreach ($actionFiles as $actionFile) {
            $actionClass = self::ACTIONS_NAMESPACE . pathinfo($actionFile, PATHINFO_FILENAME);

            if (!$this->shouldBeRegistered($actionClass, AutomationActionInterface::class, $onlyBase)) {
                continue;
            }

            $classes[] = $actionClass;
            $this->app->bind($actionClass);
            AutomationAction::registerChildCallback($actionClass);
        }

        return $classes;
    }
}
