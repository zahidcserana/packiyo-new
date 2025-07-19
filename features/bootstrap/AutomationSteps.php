<?php

use App\Components\AutomationComponent;
use App\Enums\IsoWeekday;
use App\Events\OrderAgedEvent;
use App\Events\OrderCreatedEvent;
use App\Events\OrderShippedEvent;
use App\Events\OrderUpdatedEvent;
use App\Events\PurchaseOrderClosedEvent;
use App\Interfaces\AutomationConditionInterface;
use App\Models\{Automation,
    AutomationAction,
    AutomationActions\SetWarehouseAction,
    Automations\ConstComparison,
    AutomationActions\SetDateFieldAction,
    Automations\OrderAutomation,
    Automations\SetDateFieldActionDayConfiguration,
    Automations\SetDateFieldActionMonthConfiguration,
    Automations\SetDateFieldActionWeekConfiguration,
    AutomationConditions\OrderChannelCondition,
    AutomationConditions\EventCustomerTypeCondition,
    AutomationConditions\OrderEventSourceCondition,
    AutomationConditions\SalesChannelCondition,
    AutomationConditions\ShipToCountryCondition,
    AutomationConditions\ShipToCustomerNameCondition,
    AutomationConditions\ShipToStateCondition,
    AutomationConditions\QuantityDistinctSkuCondition,
    AutomationConditions\QuantityOrderItemsCondition,
    AutomationConditions\ShippingOptionCondition,
    AutomationConditions\SubtotalOrderAmountCondition,
    AutomationConditions\TotalOrderAmountCondition,
    AutomationActions\AddLineItemAction,
    AutomationCondition,
    Customer,
    Order,
    OrderChannel,
    Product,
    ShippingBox,
    ShippingMethod,
    ShippingMethodMapping,
    Tag,
    Warehouse};
use App\Models\AutomationActions\AddPackingNoteAction;
use App\Models\AutomationActions\AddTagsAction;
use App\Models\AutomationActions\CancelOrderAction;
use App\Models\AutomationActions\MarkAsFulfilledAction;
use App\Models\AutomationActions\SetDeliveryConfirmationAction;
use App\Models\AutomationActions\SetFlagAction;
use App\Models\AutomationActions\SetPackingDimensionsAction;
use App\Models\AutomationActions\SetShippingBoxAction;
use App\Models\AutomationActions\SetShippingMethodAction;
use App\Models\Automations\AppliesToCustomers;
use App\Models\AutomationActions\SetTextFieldAction;
use App\Models\AutomationEventConditions\OrderAgedEventCondition;
use App\Models\Automations\AppliesToItemsQuantity;
use App\Models\Automations\InsertMethod;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\OrderNumberField;
use App\Models\Automations\TextComparison;
use App\Models\Automations\TimeUnit;
use App\Models\Automations\WeightUnit;
use App\Models\Automations\AppliesToLineItems;
use App\Models\Automations\AppliesToItemsTags;
use App\Models\Automations\AppliesToOperationTags;
use App\Models\AutomationConditions\OrderFlagCondition;
use App\Models\AutomationConditions\OrderIsManualCondition;
use App\Models\AutomationConditions\OrderItemTagsCondition;
use App\Models\AutomationConditions\OrderLineItemCondition;
use App\Models\AutomationConditions\OrderTagsCondition;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\PatternComparison;
use App\Models\Automations\PurchaseOrderAutomation;
use App\Models\AutomationConditions\OrderLineItemsCondition;
use App\Models\AutomationConditions\OrderNumberFieldCondition;
use App\Models\AutomationConditions\OrderTextFieldCondition;
use App\Models\AutomationConditions\OrderTextPatternCondition;
use App\Models\AutomationConditions\OrderWeightCondition;
use App\Models\AutomationConditions\OrderNumberCondition;
use Behat\Gherkin\Node\TableNode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Behat steps to test automations.
 */
trait AutomationSteps
{
    protected Automation|null $automationInScope = null;
    protected AutomationCondition|null $conditionInScope = null;
    protected AutomationAction|null $actionInScope = null;
    protected static array $continentToRegionCode = [
        'Africa' => '002',
        'Oceania' => '009',
        'America' => '019',
        'Asia' => '142',
        'Europe' => '150',
        'Other' => ''
    ];

    /**
     * @Given an order automation named :automationName owned by :customerName is enabled
     */
    public function anOrderAutomationNamedIsEnabled(string $automationName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope = OrderAutomation::factory()->create([
            'customer_id' => $customer->id,
            'name' => $automationName,
            'is_enabled' => true,
            'target_events' => [OrderCreatedEvent::class]
        ]);
    }

    /**
     * @Given an order automation named :automationName for update event owned by :customerName is enabled
     */
    public function anOrderAutomationNamedForUpdateEventIsEnabled(string $automationName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope = OrderAutomation::factory()->create([
            'customer_id' => $customer->id,
            'name' => $automationName,
            'is_enabled' => true,
            'target_events' => [OrderUpdatedEvent::class]
        ]);
    }


    /**
     * @Given an order automation named :automationName owned by :customerName is disabled
     */
    public function anOrderAutomationNamedIsDisabled(string $automationName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->automationInScope = OrderAutomation::factory()->create([
            'customer_id' => $customer->id,
            'name' => $automationName,
            'is_enabled' => false,
            'target_events' => [OrderCreatedEvent::class]
        ]);
    }

    /**
     * @Given the automation applies to the warehouse :warehouseName
     */
    public function theAutomationAppliesToTheWarehouse(string $warehouseName): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();
        $this->automationInScope->applies_to = AppliesToCustomers::SOME;
        $this->automationInScope->appliesToCustomers()->attach($warehouse->customer_id);
        $this->automationInScope->save();
    }

    /**
     * @Given a purchase order automation named :automationName owned by :customerName is enabled
     */
    public function aPurchaseOrderAutomationNamedIsEnabled(string $automationName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope = PurchaseOrderAutomation::factory()->create([
            'customer_id' => $customer->id,
            'name' => $automationName,
            'is_enabled' => true,
            'target_events' => [PurchaseOrderCreatedEvent::class]
        ]);
    }

    /**
     * @Given the automation applies to all 3PL clients
     */
    public function theAutomationAppliesToAllPlClients()
    {
        $this->automationInScope->applies_to = AppliesToCustomers::ALL;
        $this->automationInScope->save();
    }

    /**
     * @Given the automation applies to 3pl
     */
    public function theAutomationAppliesTo3pl()
    {
        $this->automationInScope->applies_to = AppliesToCustomers::OWNER;
        $this->automationInScope->save();
    }


    /**
     * @Given the automation applies to some 3PL clients
     */
    public function theAutomationAppliesToSomePlClients(): void
    {
        $this->automationInScope->applies_to = AppliesToCustomers::SOME;
        $this->automationInScope->save();
    }

    /**
     * @Given the automation applies to the 3PL client :customerName
     */
    public function theAutomationAppliesToThePlClient(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope->applies_to = AppliesToCustomers::SOME;
        $this->automationInScope->appliesToCustomers()->attach($customer->id);
        $this->automationInScope->save();
    }

    /**
     * @Given the automation applies to all but the 3PL client :customerName
     */
    public function theAutomationAppliesToAllButThePlClient(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope->applies_to = AppliesToCustomers::NOT_SOME;
        $this->automationInScope->appliesToCustomers()->attach($customer->id);
        $this->automationInScope->save();
    }

    /**
     * @Given the automation is triggered when a new order from the channel :channelName is received
     */
    public function theAutomationIsTriggeredWhenANewOrderFromTheChannelIsReceived(string $channelName): void
    {
        $customers = self::getStandaloneOr3plClients($this->automationInScope);
        $orderChannel = OrderChannel::where('name', $channelName)
            ->whereIn('customer_id', $customers->pluck('id'))
            ->firstOrFail();
        $this->conditionInScope = OrderChannelCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'order_channel_id' => $orderChannel->id
        ]);
    }

    /**
     * @Given the automation adds :quantity of the SKU :sku
     */
    public function theAutomationAddsOfTheSku(string $quantity, string $sku): void
    {
        $customers = self::getStandaloneOr3plClients($this->automationInScope);
        $product = Product::where(['sku' => $sku])
            ->whereIn('customer_id', $customers->pluck('id'))
            ->firstOrFail();
        $this->actionInScope = AddLineItemAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'product_id' => $product->id,
            'quantity' => $quantity
        ]);
    }

    /**
     * @Given the automation adds :quantity of the SKU :sku and force flag :force
     */
    public function theAutomationAddsOfTheSkuAndForce(string $quantity, string $sku, string $force): void
    {
        $customers = self::getStandaloneOr3plClients($this->automationInScope);
        $product = Product::where(['sku' => $sku])
            ->whereIn('customer_id', $customers->pluck('id'))
            ->firstOrFail();
        AddLineItemAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'force' => self::onOrOffToBool($force)
        ]);
    }

    /**
     * @Given the automation forces adding :quantity of the SKU :sku
     */
    public function theAutomationForcesAddingOfTheSku(string $quantity, string $sku): void
    {
        $customers = self::getStandaloneOr3plClients($this->automationInScope);
        $product = Product::where(['sku' => $sku])
            ->whereIn('customer_id', $customers->pluck('id'))
            ->firstOrFail();
        $this->actionInScope = AddLineItemAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'force' => true
        ]);
    }

    /**
     * @Given the automation adds the packing note :note
     */
    public function theAutomationAddsThePackingNote(string $note): void
    {
        AddPackingNoteAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text' => $note,
            'insert_method' => InsertMethod::APPEND
        ]);
    }

    /**
     * @Given the automation :automationName is moved to position number :position
     */
    public function theAutomationIsMovedToPositionNumber(string $automationName, string $position): void
    {
        $automation = Automation::where('name', $automationName)->firstOrFail();
        $automation->move($position);
    }

    /**
     * @Given the automation is triggered when a new order has these tags
     */
    public function theAutomationIsTriggeredWhenANewOrderHasTheseTags(TableNode $tagsTable): void
    {
        $tags = self::getOrCreateTags($this->automationInScope->customer_id, $tagsTable->getRow(0));
        $this->conditionInScope = OrderTagsCondition::factory()
            ->hasAttached($tags)
            ->create([
                'automation_id' => $this->automationInScope->id,
                'applies_to' => AppliesToOperationTags::ALL
            ]);
    }

    /**
     * @Given the automation item is triggered when a new order has these tags
     */
    public function theAutomationItemIsTriggeredWhenANewOrderHasTheseTags(TableNode $tagsTable): void
    {
        $tags = self::getOrCreateTags($this->automationInScope->customer_id, $tagsTable->getRow(0));

        $this->conditionInScope = OrderItemTagsCondition::factory()
            ->hasAttached($tags)
            ->create([
                'automation_id' => $this->automationInScope->id,
                'applies_to' => AppliesToItemsTags::ALL
            ]);
    }

    /**
     * @Given the automation is triggered when a new order has some of these tags
     */
    public function theAutomationIsTriggeredWhenANewOrderHasSomeOfTheseTags(TableNode $tagsTable): void
    {
        $tags = self::getOrCreateTags($this->automationInScope->customer_id, $tagsTable->getRow(0));
        $this->conditionInScope = OrderTagsCondition::factory()
            ->hasAttached($tags)
            ->create([
                'automation_id' => $this->automationInScope->id,
                'applies_to' => AppliesToOperationTags::SOME
            ]);
    }

    /**
     * @Given the automation is triggered when a new order has none of these tags
     */
    public function theAutomationIsTriggeredWhenANewOrderHasNoneOfTheseTags(TableNode $tagsTable): void
    {
        $tags = self::getOrCreateTags($this->automationInScope->customer_id, $tagsTable->getRow(0));
        $this->conditionInScope = OrderTagsCondition::factory()
            ->hasAttached($tags)
            ->create([
                'automation_id' => $this->automationInScope->id,
                'applies_to' => AppliesToOperationTags::NONE
            ]);
    }

    /**
     * @Given the automation adds these tags
     */
    public function theAutomationAddsTheseTags(TableNode $tagsTable): void
    {
        $tags = self::getOrCreateTags($this->automationInScope->customer_id, $tagsTable->getRow(0));
        AddTagsAction::factory()
            ->hasAttached($tags)
            ->create(['automation_id' => $this->automationInScope->id]);
    }

    private static function getOrCreateTags(int $customerId, array $tagNames): array
    {
        return array_map(
            fn (string $tagName) => Tag::firstOrCreate(['customer_id' => $customerId, 'name' => $tagName]),
            $tagNames
        );
    }

    /**
     * @Given the automation is triggered when a new order has a line item with these tags
     */
    public function theAutomationIsTriggeredWhenANewOrderHasALineItemWithTheseTags(TableNode $tagsTable): void
    {
        $tags = self::getOrCreateTags($this->automationInScope->customer_id, $tagsTable->getRow(0));
        $this->conditionInScope = OrderItemTagsCondition::factory()
            ->hasAttached($tags)
            ->create([
                'automation_id' => $this->automationInScope->id,
                'applies_to' => AppliesToItemsTags::SOME
            ]);
    }

    /**
     * @Given the automation is triggered when all line items have these tags
     */
    public function theAutomationIsTriggeredWhenAllLineItemsHaveTheseTags(TableNode $tagsTable): void
    {
        $tags = self::getOrCreateTags($this->automationInScope->customer_id, $tagsTable->getRow(0));
        $this->conditionInScope = OrderItemTagsCondition::factory()
            ->hasAttached($tags)
            ->create([
                'automation_id' => $this->automationInScope->id,
                'applies_to' => AppliesToItemsTags::ALL
            ]);
    }

    /**
     * @Given the automation is triggered when none of the line items have these tags
     */
    public function theAutomationIsTriggeredWhenNoneOfTheLineItemsHaveTheseTags(TableNode $tagsTable): void
    {
        $tags = self::getOrCreateTags($this->automationInScope->customer_id, $tagsTable->getRow(0));
        $this->conditionInScope = OrderItemTagsCondition::factory()
            ->hasAttached($tags)
            ->create([
                'automation_id' => $this->automationInScope->id,
                'applies_to' => AppliesToItemsTags::NONE
            ]);
    }

    /**
     * @Given the conditions are alternatives to each other
     */
    public function theConditionsAreAlternativesToEachOther()
    {
        $this->automationInScope->conditions->sortBy('position')
            ->skip(1)
            ->map(function (AutomationConditionInterface $trigger) {
                $trigger->is_alternative = true;
                $trigger->save();
            });
    }

    /**
     * @Given the automation is triggered when an order with flag :flagName toggled :flagValue is received
     */
    public function theAutomationIsTriggeredWhenAnOrderWithFlagToggledIsReceived(string $flagName, string $flagValue): void
    {
        $this->conditionInScope = OrderFlagCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => $flagName,
            'flag_value' => self::onOrOffToBool($flagValue)
        ]);
    }

    /**
     * @Given the automation is triggered when the order number is one of these
     */
    public function theAutomationIsTriggeredWhenAnOrderNumberIsReceived(TableNode $orderNumbers): void
    {
        $this->conditionInScope = OrderNumberCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => $orderNumbers->getRow(0),
            'comparison_operator' => TextComparison::SOME_EQUALS,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation sets the flag :flagName to :flagValue
     */
    public function theAutomationSetsTheFlagTo(string $flagName, string $flagValue): void
    {
        SetFlagAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => $flagName,
            'flag_value' => self::onOrOffToBool($flagValue)
        ]);
    }

    /**
     * @Given the automation is disabled
     */
    public function theAutomationIsDisabled()
    {
        $this->automationInScope->is_enabled = false;
        $this->automationInScope->save();
    }

    /**
     * @Given the automation is triggered when an order is updated
     */
    public function theAutomationIsTriggeredWhenAnOrderIsUpdated()
    {
        $this->automationInScope->target_events = [OrderUpdatedEvent::class];
        $this->automationInScope->save();
    }

    /**
     * @Given the automation is also triggered when an order is updated
     */
    public function theAutomationIsAlsoTriggeredWhenAnOrderIsUpdated()
    {
        $this->automationInScope->target_events = array_unique(array_merge(
            $this->automationInScope->target_events, [OrderUpdatedEvent::class]
        ));
        $this->automationInScope->save();
    }

    /**
     * @Given the automation is triggered when an order is shipped
     */
    public function theAutomationIsTriggeredWhenAnOrderIsShipped()
    {
        $this->automationInScope->target_events = [OrderShippedEvent::class];
        $this->automationInScope->save();
    }

    /**
     * @Given the automation is triggered when the order has the SKU :sku
     */
    public function theAutomationIsTriggeredWhenTheOrderHasTheSku(string $sku): void
    {
        $customers = self::getStandaloneOr3plClients($this->automationInScope);
        $product = Product::where(['sku' => $sku])
            ->whereIn('customer_id', $customers->pluck('id'))
            ->firstOrFail();
        $trigger = OrderLineItemCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToLineItems::SOME,
            'number_field_value' => 1,
            'comparison_operator' => NumberComparison::GREATER_OR_EQUAL
        ]);
        $trigger->matchesProducts()->attach($product->id);
    }

    /**
     * @Given the automation is triggered when the order is for at least :quantity units total
     */
    public function theAutomationIsTriggeredWhenTheOrderIsForAtLeastUnitsTotal(string $quantity): void
    {
        OrderLineItemsCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToItemsQuantity::TOTAL,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::GREATER_OR_EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when the order items quantity is at least :quantity units total
     */
    public function theAutomationIsTriggeredWhenTheOrderItemsQuantityIsLeastUnitsTotal(string $quantity): void
    {
        QuantityOrderItemsCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToItemsQuantity::TOTAL,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::GREATER_OR_EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when a line item in the order is for at least :quantity units
     */
    public function theAutomationIsTriggeredWhenALineItemInTheOrderIsForAtLeastUnits(string $quantity): void
    {
        OrderLineItemsCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToItemsQuantity::ANY,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::GREATER_OR_EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when no line item in the order is for less than :quantity units
     */
    public function theAutomationIsTriggeredWhenNoLineItemInTheOrderIsForMoreThanUnits(string $quantity): void
    {
        OrderLineItemsCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToItemsQuantity::NONE,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::LESSER
        ]);
    }

    /**
     * @Given the automation is triggered when all line items in the order are for exactly :quantity units
     */
    public function theAutomationIsTriggeredWhenAllLineItemsInTheOrderAreForExactlyUnits(string $quantity): void
    {
        OrderLineItemsCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToItemsQuantity::EACH,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when the sales channel requested the :shippingMethodName shipping method
     */
    public function theAutomationIsTriggeredWhenTheSalesChannelRequestedTheShippingMethod(string $shippingMethodName): void
    {
        $this->conditionInScope = OrderTextFieldCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => OrderTextField::CHANNEL_SHIPPING_OPTION,
            'text_field_values' => [$shippingMethodName],
            'comparison_operator' => TextComparison::SOME_EQUALS,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation is triggered when the ship to country is :countryCode
     */
    public function theAutomationIsTriggeredWhenTheShipToCountryIs(string $countryCode): void
    {
        $this->conditionInScope = ShipToCountryCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => [$countryCode],
            'comparison_operator' => TextComparison::SOME_EQUALS,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation is triggered when the ship to state are
     */

    public function theAutomationIsTriggeredWhenTheShipToStateIs(TableNode $statesTable): void
    {
        $this->conditionInScope = ShipToStateCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => $statesTable->getRow(0),
            'comparison_operator' => TextComparison::SOME_EQUALS,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation is triggered when the ship to customer name is :customerName
     */
    public function theAutomationIsTriggeredWhenTheShipToCustomerNameIs(string $customerName): void
    {
        $this->conditionInScope = ShipToCustomerNameCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => $customerName,
            'comparison_operator' => TextComparison::SOME_EQUALS,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation sets the shipping carrier :carrierName and the shipping method :methodName
     */
    public function theAutomationSetsTheShippingCarrierAndTheShippingMethod(string $carrierName, string $methodName): void
    {
        $this->createSetShippingMethodAction($carrierName, $methodName);
    }

    /**
     * @Given the automation forces setting the shipping carrier :carrierName and the shipping method :methodName
     */
    public function theAutomationForcesSettingTheShippingCarrierAndTheShippingMethod(string $carrierName, string $methodName): void
    {
        $this->createSetShippingMethodAction($carrierName, $methodName, force: true);
    }


    /**
     * @Given the automation is triggered when the shipping method requested by the sales channel starts with one of
     */
    public function theAutomationIsTriggeredWhenTheShippingMethodRequestedByTheSalesChannelStartsWithOneOf(TableNode $methodsTable)
    {
        $this->conditionInScope = OrderTextFieldCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => OrderTextField::CHANNEL_SHIPPING_OPTION,
            'text_field_values' => $methodsTable->getRow(0),
            'comparison_operator' => TextComparison::SOME_STARTS_WITH,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation is triggered when the shipping option starts with one of
     */
    public function theAutomationIsTriggeredWhenTheShippingOptionStartsWithOneOf(TableNode $methodsTable)
    {
        $this->conditionInScope = ShippingOptionCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => $methodsTable->getRow(0),
            'comparison_operator' => TextComparison::SOME_STARTS_WITH,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation is triggered when the shipping method requested by the sales channel ends with one of
     */
    public function theAutomationIsTriggeredWhenTheShippingMethodRequestedByTheSalesChannelEndsWithOneOf(TableNode $methodsTable)
    {
        $this->conditionInScope = OrderTextFieldCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => OrderTextField::CHANNEL_SHIPPING_OPTION,
            'text_field_values' => $methodsTable->getRow(0),
            'comparison_operator' => TextComparison::SOME_ENDS_WITH,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation is triggered when the shipping method requested by the sales channel contains one of
     */
    public function theAutomationIsTriggeredWhenTheShippingMethodRequestedByTheSalesChannelContainsOneOf(TableNode $methodsTable)
    {
        $this->conditionInScope = OrderTextFieldCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => OrderTextField::CHANNEL_SHIPPING_OPTION,
            'text_field_values' => $methodsTable->getRow(0),
            'comparison_operator' => TextComparison::SOME_CONTAINS,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the trigger is case insensitive
     */
    public function theTriggerIsCaseInsensitive()
    {
        $this->conditionInScope->case_sensitive = false;
        $this->conditionInScope->save();
    }

    /**
     * @Given the automation is triggered when the weight is :operator :weight :unitOfMeasure
     */
    public function theAutomationIsTriggeredWhenTheWeightIs(string $operator, string $weight, string $unitOfMeasure): void
    {
        $unitOfMeasure = collect(WeightUnit::cases())
            ->firstOrFail(fn (WeightUnit $unit) => $unit->value == $unitOfMeasure);
        $this->conditionInScope = OrderWeightCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'comparison_operator' => NumberComparison::tryFrom($operator),
            'number_field_value' => $weight,
            'unit_of_measure' => $unitOfMeasure
        ]);
    }

    /**
     * @Given the automation is triggered when an order turns :age :timeUnit old
     */
    public function theAutomationIsTriggeredWhenAnOrderTurnsOld(string $age, string $timeUnit): void
    {
        $this->automationInScope->target_events = [OrderAgedEvent::class];
        $this->automationInScope->save();
        OrderAgedEventCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'number_field_value' => $age,
            'unit_of_measure' => TimeUnit::tryFrom($timeUnit),
            'pending_only' => true,
            'ignore_holds' => true
        ]);
    }

    /**
     * @Given the automation is triggered when the order channel name starts with one of
     */
    public function theAutomationIsTriggeredWhenTheOrderChannelNameStartsWithOneOf(TableNode $namesTable)
    {
        $this->conditionInScope = SalesChannelCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => $namesTable->getRow(0),
            'comparison_operator' => TextComparison::SOME_STARTS_WITH,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation is triggered when a new manual order is created
     */
    public function theAutomationIsTriggeredWhenANewManualOrderIsCreated()
    {
        $this->conditionInScope = OrderIsManualCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'flag_value' => true
        ]);
    }

    /**
     * @Given the automation sets the shipping box :boxName of customer :customerName
     */
    public function theAutomationSetsTheShippingBoxOfCustomer(string $boxName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $box = ShippingBox::where(['customer_id' => $customer->id, 'name' => $boxName])->firstOrFail();

        SetShippingBoxAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'shipping_box_id' => $box->id
        ]);
    }

    /**
     * @Given the automation is triggered when the order has a total of :quantity items
     */
    public function theAutomationIsTriggeredWhenTheOrderHasATotalOfItems(string $quantity): void
    {
        $this->conditionInScope = OrderLineItemCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToLineItems::ALL,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when the total distinct sku is :quantity items
     */
    public function theAutomationIsTriggeredWhenTheTotalDistinctSkuIs(string $quantity): void
    {
        $this->conditionInScope = QuantityDistinctSkuCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToLineItems::ALL,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when the order has at least :quantity items
     */
    public function theAutomationIsTriggeredWhenTheOrderHasAtLeastItems(string $quantity): void
    {
        $this->conditionInScope = OrderLineItemCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToLineItems::ALL,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::GREATER_OR_EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when the order has at most :quantity items
     */
    public function theAutomationIsTriggeredWhenTheOrderHasAtMostItems(string $quantity): void
    {
        $this->conditionInScope = OrderLineItemCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'applies_to' => AppliesToLineItems::ALL,
            'number_field_value' => $quantity,
            'comparison_operator' => NumberComparison::LESSER_OR_EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when the field :fieldName on an order :operator the pattern :pattern
     */
    public function theAutomationIsTriggeredWhenTheFieldOnAnOrderThePattern(
        string $fieldName, string $operator, string $pattern
    ): void
    {
        $this->conditionInScope = OrderTextPatternCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => OrderTextField::tryFrom($fieldName),
            'text_pattern' => $pattern,
            'comparison_operator' => PatternComparison::tryFrom($operator)
        ]);
    }

    /**
     * @Given the automation is triggered when the ship to country is not :countryCode
     */
    public function theAutomationIsTriggeredWhenTheShipToCountryIsNot(string $countryCode): void
    {
        $this->conditionInScope = ShipToCountryCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => [$countryCode],
            'comparison_operator' => TextComparison::NONE_EQUALS,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation marks the order as fulfilled
     */
    public function theAutomationMarksTheOrderAsFulfilled()
    {
        MarkAsFulfilledAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'ignore_cancelled' => false
        ]);
    }

    /**
     * @Given the automation cancels the order
     */
    public function theAutomationCancelsTheOrder()
    {
        CancelOrderAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'ignore_fulfilled' => false
        ]);
    }

    /**
     * @Given the automation is triggered when a purchase order is closed
     */
    public function theAutomationIsTriggeredWhenAPurchaseOrderIsClosed()
    {
        $this->automationInScope->target_events = [PurchaseOrderClosedEvent::class];
        $this->automationInScope->save();
    }

    /**
     * @Given the automation is triggered when the ship to country is none of
     */
    public function theAutomationIsTriggeredWhenTheShipToCountryIsNoneOf(TableNode $countryCodesTable): void
    {
        $countryCodes = $countryCodesTable->getRow(0);
        $this->conditionInScope = OrderTextFieldCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => OrderTextField::SHIPPING_COUNTRY_CODE,
            'text_field_values' => $countryCodes,
            'comparison_operator' => TextComparison::NONE_EQUALS,
            'case_sensitive' => true
        ]);
    }


    /**
     * @Given the action ignores fulfilled
     */
    public function itIgnoresFulfilled()
    {
        $this->actionInScope->ignore_fulfilled = true;
        $this->actionInScope->save();
    }

    /**
     * @Given the action ignores cancelled
     */
    public function itIgnoresCancelled()
    {
        $this->actionInScope->ignore_cancelled = true;
        $this->actionInScope->save();
    }


    /**
     * @Given the automation is triggered when the order total is :operator :number
     * */
    public function theAutomationIsTriggeredWhenTheOrderTotalIs(string $operator, string $number): void
    {
        $this->conditionInScope = TotalOrderAmountCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'number_field_value' => $number,
            'comparison_operator' => NumberComparison::tryFrom($operator)
        ]);
    }

    /**
     * @Given the automation is triggered when subtotal is :subtotal
     */
    public function theAutomationIsTriggeredWhenTheSubtotalIs(string $subtotal): void
    {
        $this->conditionInScope = SubtotalOrderAmountCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'number_field_value' => $subtotal,
            'comparison_operator' => NumberComparison::EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when total is :total
     */
    public function theAutomationIsTriggeredWhenThetotalIs(string $total): void
    {
        $this->conditionInScope = TotalOrderAmountCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'number_field_value' => $total,
            'comparison_operator' => NumberComparison::EQUAL
        ]);
    }

    /**
     * @Given the automation is triggered when the shipping cost is :operator :number
     */
    public function theAutomationIsTriggeredWhenTheShippingCostIs(string $operator, string $number): void
    {
        $this->conditionInScope = OrderNumberFieldCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => OrderNumberField::SHIPPING,
            'number_field_value' => $number,
            'comparison_operator' => NumberComparison::tryFrom($operator)
        ]);
    }

    /**
     * @Given the automation sets the packing dimensions based on the order items using the :boxName box
     */
    public function theAutomationSetsThePackingDimensionsBasedOnTheOrderItemsUsingTheBox(string $boxName): void
    {
        $box = ShippingBox::firstOrCreate(['customer_id' => $this->getCustomerInScope()->id, 'name' => $boxName]);
        SetPackingDimensionsAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'shipping_box_id' => $box->id
        ]);
    }

    /**
     * @Given the automation is triggered when the ship to continent is not
     */
    public function theAutomationIsTriggeredWhenTheShipToContinentIsNot(TableNode $continentsTable)
    {
        $regionCodes = array_map(
            fn (string $continent) => static::$continentToRegionCode[$continent],
            $continentsTable->getRow(0)
        );
        $this->conditionInScope = OrderTextFieldCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => OrderTextField::CHANNEL_SHIPPING_OPTION,
            'text_field_values' => $regionCodes,
            'comparison_operator' => TextComparison::NONE_EQUALS,
            'case_sensitive' => true
        ]);
    }

    /**
     * @Given the automation sets the delivery confirmation to :value
     */
    public function theAutomationSetsTheDeliveryConfirmationTo(string $value): void
    {
        SetDeliveryConfirmationAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_value' => $value
        ]);
    }

    /**
     * @Given the automation sets the field :fieldName to :fieldValue
     */
    public function theAutomationSetsTheFieldTo(string $fieldName, string $fieldValue): void
    {
        SetTextFieldAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => $fieldName,
            'text_field_value' => $fieldValue
        ]);
    }

    /**
     * @Given the channel shipping option :shippingMethodName is mapped to the carrier :carrierName and the method :methodName
     */
    public function theChannelShippingOptionIsMappedToTheCarrierAndTheMethod(
        string $shippingMethodName,
        string $carrierName,
        string $methodName
    ): void {
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use ($carrierName) {
            $query->where('name', $carrierName);
        })
            ->where('name', $methodName)
            ->firstOrFail();
        ShippingMethodMapping::factory()->create([
            'customer_id' => $shippingMethod->shippingCarrier->customer_id,
            'shipping_method_id' => $shippingMethod->id,
            'return_shipping_method_id' => $shippingMethod->id,
            'shipping_method_name' => $shippingMethodName
        ]);
    }

    /**
     * @Given the automation sets the warehouse :warehouse of customer :customerName
     */
    public function theAutomationSetsTheWarehouseOfCustomer(string $warehouseName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $warehouse = $customer->warehouses()->whereHas('contactInformation', fn (Builder $query) => $query->where('name', $warehouseName))
            ->firstOrFail();

        SetWarehouseAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'warehouse_id' => $warehouse->id
        ]);
    }

    /**
     * @When a cron runs the timed automations
     */
    public function aCronRunsTheTimedAutomations(): void
    {
        App::make(AutomationComponent::class)->runTimedAutomations();
    }

    /**
     * @When I add the client :customerName to the automation
     */
    public function iAddTheClientToTheAutomation(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope = $this->automationInScope->revise()->addCustomer($customer)->get();
    }

    /**
     * @When I remove the client :customerName to the automation
     */
    public function iRemoveTheClientToTheAutomation(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope = $this->automationInScope->revise()->removeCustomer($customer)->get();
    }

    /**
     * @When I disable the automation
     */
    public function iDisableTheAutomation()
    {
        $this->automationInScope->is_enabled = false;
        $this->automationInScope->save();
    }

    /**
     * @When I rename the automation to :name
     */
    public function iRenameTheAutomationTo(string $name): void
    {
        $this->automationInScope->name = $name;
        $this->automationInScope->save();
    }

    /**
     * @Then I should find order :orderNumber when searching by automation :automationName and event :eventName
     */
    public function iShouldFindOrderWhenSearchingByAutomationAndEvent(
        string $orderNumber, string $automationName, string $eventName
    ): void
    {
        $this->automationInScope = Automation::where([
            'customer_id' => $this->getCustomerInScope()->id,
            'name' => $automationName
        ])->firstOrFail();
        $order = $this->automationInScope->actedOnOperations()
            ->withPivot('target_event', 'created_at')
            ->where('number', $orderNumber)
            ->wherePivot('target_event', $eventName)
            ->first();

        $this->assertNotNull($order);
        $this->assertNotNull($order->pivot->created_at);
    }

    /**
     * @Then the automation should have acted :actedOnTimes times on order :orderNumber for event :eventName
     */
    public function theAutomationShouldHaveActedTimesOnOrderForEvent(
        string $actedOnTimes, string $orderNumber, string $eventName
    )
    {
        $actedOnOrdersCount = $this->automationInScope->actedOnOperations()
            ->withPivot('target_event', 'created_at')
            ->where('number', $orderNumber)
            ->wherePivot('target_event', $eventName)
            ->count();

        $this->assertEquals($actedOnTimes, $actedOnOrdersCount);
    }

    /**
     * @Then the automation should apply to the following 3PL clients
     */
    public function theAutomationShouldApplyToTheFollowingPlClients(TableNode $customerNamesTable): void
    {
        $expectedCustomers = $customerNamesTable->getRow(0);
        sort($expectedCustomers);
        $actualCustomers = $this->automationInScope->appliesToCustomers->filter(
                fn (Customer $customer) => in_array($customer->contactInformation->name, $expectedCustomers)
            )
            ->sortBy('contactInformation.name')
            ->pluck('contactInformation.name')
            ->toArray();

        $this->assertEquals($expectedCustomers, $actualCustomers);
    }

    /**
     * @Then the automation should have :revisionAmount revisions
     */
    public function theAutomationShouldHaveRevisions(string $revisionAmount): void
    {
        $this->assertEquals($revisionAmount, $this->automationInScope->revisions->count());
    }

    /**
     * @Then the latest audit of the automation was authored by :name and logs a change in :property
     */
    public function theLatestAuditOfTheAutomationWasAuthoredByAndLogsAChangeIn(string $name, string $property): void
    {
        $audit = $this->automationInScope->audits->last();
        $this->assertArrayHasKey($property, $audit->new_values);
        $this->assertEquals($audit->new_values[$property], $this->automationInScope->$property);
        $this->assertEquals($name, $audit->user->contactInformation->name);
    }

    /**
     * @Then the automation should be named :name
     */
    public function theAutomationShouldBeNamed(string $name): void
    {
        $this->assertEquals($name, $this->automationInScope->name);
    }

    /**
     * @Given the automation applies to the warehouse :warehouseName and adds :amount :timeUnit from the creation date to the :fieldName field
     */
    public function theAutomationAddsFromTheCreationDateToTheField(string $warehouseName, string $amount, string $timeUnit, string $fieldName): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        $units = [TimeUnit::MINUTES, TimeUnit::HOURS];

        if (!in_array(TimeUnit::tryFrom($timeUnit), $units)) {
            throw new InvalidArgumentException("Invalid time unit for this step: $timeUnit");
        }

        SetDateFieldAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => $fieldName,
            'number_field_value' => $amount,
            'unit_of_measure' => TimeUnit::tryFrom($timeUnit),
            'warehouse_id' => $warehouse->id
        ]);
    }


    /**
     * @Given the automation is triggered when and order is created by a 3pl
     */
    public function theAutomationIsTriggeredWhenAndOrderIsCreatedByAPl()
    {
        $this->conditionInScope = EventCustomerTypeCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => [\App\Enums\EventUser::CUSTOMER_IS_3PL->value]
        ]);
    }

    /**
     * @Given the automation is triggered when and order is updated by a 3pl
     */
    public function theAutomationIsTriggeredWhenAndOrderIsUpdatedByAPl()
    {
        $this->theAutomationIsTriggeredWhenAndOrderIsCreatedByAPl();
    }


    /**
     * @Given the automation is triggered when and order is updated by a 3pl client
     */
    public function theAutomationIsTriggeredWhenAndOrderIsUpdatedByAPlClient()
    {
        $this->theAutomationIsTriggeredWhenAndOrderIsCreatedByAPlClient();
    }

    /**
     * @Given the automation is triggered when and order is created by a 3pl client
     */
    public function theAutomationIsTriggeredWhenAndOrderIsCreatedByAPlClient()
    {
        $this->conditionInScope = EventCustomerTypeCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => [\App\Enums\EventUser::CUSTOMER_IS_3PL_CLIENT->value]
        ]);
    }

    /**
     * @Given the automation is triggered when and order is created by :source type
     */
    public function theAutomationIsTriggeredWhenAndOrderIsCreatedBySource(string $source)
    {
        $this->conditionInScope = OrderEventSourceCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => [$source],
            'comparison_operator' => ConstComparison::IS_ONE_OF
        ]);
    }

    /**
     * @Given the automation is triggered when and order is updated by :source type
     */
    public function theAutomationIsTriggeredWhenAndOrderIsUpdatedBySource(string $source)
    {
        $this->theAutomationIsTriggeredWhenAndOrderIsCreatedBySource($source);
    }

    /**
     * @Given the automation is triggered when and order is created with at least one of these sources
     */
    public function theAutomationIsTriggeredWhenAndOrderIsCreatedByTheseSource(TableNode $sourcesTable)
    {
        $this->conditionInScope = OrderEventSourceCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => $sourcesTable->getRow(0),
            'comparison_operator' => ConstComparison::IS_ONE_OF
        ]);
    }

    /**
     * @Given the automation is triggered when and order is created with none of these sources
     */
    public function theAutomationIsTriggeredWhenAndOrderIsCreatedWithNoneOfTheseSources(TableNode $sourcesTable)
    {
        $this->conditionInScope = OrderEventSourceCondition::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'text_field_values' => $sourcesTable->getRow(0),
            'comparison_operator' => ConstComparison::IS_NONE_OF
        ]);
    }

    /**
     * @When I set customer :arg1 in scope
     */
    public function iSetCustomerInScope($customerName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->setCustomerInScope($customer);
    }

    /**
     * @Given the automation applies to the warehouse :warehouseName and adds :amount days and set time to :time on the :fieldName field
     */
    public function theAutomationAppliesToTheWarehouseAndAddsDaysAndSetTimeToOnTheField(string $warehouseName, string $amount, string $time, string $fieldName): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        SetDateFieldAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => $fieldName,
            'number_field_value' => $amount,
            'unit_of_measure' => TimeUnit::DAYS,
            'text_field_values' => new SetDateFieldActionDayConfiguration($time),
            'warehouse_id' => $warehouse->id
        ]);
    }

    /**
     * @Given the automation applies to the warehouse :warehouseName and adds :amount weeks, sets day to :day and time to :time on the :fieldName field
     */
    public function theAutomationAppliesToTheWarehouseAndAddsWeeksAndSetDayToAndTimeToOnTheField(string $warehouseName, string $amount, string $day, string $time, string $fieldName): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        SetDateFieldAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => $fieldName,
            'number_field_value' => $amount,
            'unit_of_measure' => TimeUnit::WEEKS,
            'text_field_values' => new SetDateFieldActionWeekConfiguration(IsoWeekday::fromLabel($day), $time),
            'warehouse_id' => $warehouse->id
        ]);
    }

    /**
     * @Given the automation applies to the warehouse :warehouseName and adds :amount months, sets day to :day and time to :time on the :fieldName field
     */
    public function theAutomationAppliesToTheWarehouseAndAddsMonthsAndSetDayToAndTimeToOnTheField(string $warehouseName, string $amount, string $day, string $time, string $fieldName): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

      SetDateFieldAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'field_name' => $fieldName,
            'number_field_value' => $amount,
            'unit_of_measure' => TimeUnit::MONTHS,
            'text_field_values' => new SetDateFieldActionMonthConfiguration($day, $time),
            'warehouse_id' => $warehouse->id
        ]);
    }

    public function createSetShippingMethodAction(string $carrierName, string $methodName, bool $force = false): void
    {
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use ($carrierName) {
            $query->where('name', $carrierName);
        })
            ->where('name', $methodName)
            ->firstOrFail();
        SetShippingMethodAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'shipping_method_id' => $shippingMethod->id,
            'force' => $force
        ]);
    }

    /**
     * @Given the automation condition should be this on db
     */
    public function theAutomationConditionShouldByThisOneDB(TableNode $tagsTable): void
    {
        $conditionDB = AutomationCondition::findOrfail($this->automationInScope->conditions->first()->id);
        $row = $this->parseRowNull($tagsTable->getRow(0));

        $this->assertEquals($conditionDB->type, $row[0]);
        $this->assertEquals($conditionDB->position, $row[1]);
        $this->assertEquals($conditionDB->is_alternative, $row[2]);
        $this->assertEquals($conditionDB->field_name?->value, $row[3]);
        $this->assertEquals($conditionDB->comparison_operator?->value, $row[4]);
        $this->assertEquals($conditionDB->unit_of_measure, $row[5]);
        $this->assertEquals($conditionDB->flag_value, $row[6]);
        $this->assertEquals($conditionDB->number_field_value, $row[7]);
        $this->assertEquals(is_array($conditionDB->text_field_values) ?
            implode(',', $conditionDB->text_field_values) :
            $conditionDB->text_field_values, $row[8]);
        $this->assertEquals($conditionDB->automation_id, $this->automationInScope->id);
        $this->assertEquals($conditionDB->order_channel_id, $row[9]);
        $this->assertEquals($conditionDB->applies_to?->value, $row[10]);
        $this->assertEquals($conditionDB->text_pattern, $row[11]);
        $this->assertEquals($conditionDB->case_sensitive, $row[12]);
    }

    private function parseRowNull(array $row) : array
    {
        return array_map(function($value) {
            return $value === 'NULL' ? null : $value;
        }, $row);
    }
}
