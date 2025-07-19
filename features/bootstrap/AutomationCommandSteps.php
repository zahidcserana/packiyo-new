<?php

use App\Interfaces\AutomationActionInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\{
    Automation,
    Customer
};
use App\Models\Automations\AppliesToCustomers;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Behat steps to test customers.
 */
trait AutomationCommandSteps
{
    protected array|null $commandFuncArray = null;

    protected static function createMethodCall(string $name, array $arguments): stdClass
    {
        $methodCall = new stdClass();
        $methodCall->name = $name;
        $methodCall->arguments = $arguments;

        return $methodCall;
    }

    /**
     * @Given I will run the :commandName command
     */
    public function iWillRunTheCommand(string $commandName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('artisan', [$commandName]);
    }

    /**
     * @Given I will choose for the automation to be owned by the customer :customerName
     */
    public function iWillChooseForTheAutomationToBeOwnedByTheCustomer(string $customerName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which customer should own the automation?'),
            $customerName
        ]);
    }

    /**
     * @Given I will choose for the automation to apply to all 3PL clients
     */
    public function iWillChooseForTheAutomationToApplyToAllPlClients(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which clients should the automation apply to?'),
            __('all')
        ]);
    }

    /**
     * @Given I will choose for the automation to apply to the 3PL client :customerName
     */
    public function iWillChooseForTheAutomationToApplyToThePlClient(string $customerName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which clients should the automation apply to?'),
            __('some')
        ]);
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which ones should be included? (Case-sensitive, comma-separated, tab to auto-complete.)'),
            [$customerName]
        ]);
    }

    /**
     * @Given I will choose for the automation to apply to all but the 3PL client :customerName
     */
    public function iWillChooseForTheAutomationToApplyToAllButThePlClient(string $customerName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which clients should the automation apply to?'),
            __('not_some')
        ]);
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which ones should be excluded? (Case-sensitive, comma-separated, tab to auto-complete.)'),
            [$customerName]
        ]);
    }

    /**
     * @Given I will choose :automatableEvent as the only triggering event
     */
    public function iWillChooseAsTheOnlyTriggeringEvent(string $automatableEvent): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            'Which event should trigger the automation?',
            $automatableEvent
        ]);
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want to add another event?'),
            false
        ]);
    }

    /**
     * @Given I will choose to add the condition :conditionName
     */
    public function iWillChooseToAddTheTrigger(string $conditionName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want to add a condition criteria?'),
            true
        ]);
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which condition do you want to add?'),
            $conditionName
        ]);
    }

    /**
     * @Given I will choose to also add the condition :conditionName
     */
    public function iWillChooseToAlsoAddTheTrigger(string $conditionName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which condition do you want to add?'),
            $conditionName
        ]);
    }

    /**
     * @Given I will choose not to add a condition when prompted
     */
    public function iWillChooseNotToAddATriggerWhenPrompted(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want to add a condition criteria?'),
            false
        ]);
    }

    /**
     * @Given I will choose 3PL :threePlName
     */
    public function iWillChoosePl(string $threePlName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which customer?'),
            $threePlName
        ]);
    }

    /**
     * @Given I will choose the automation :automationName
     */
    public function iWillChooseTheAutomation(string $automationName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which automation do you want to assign?'),
            $automationName
        ]);
    }

    /**
     * @Given I will choose to unassign automations from the customer :customerName
     */
    public function iWillChooseToUnassignFromTheCustomer(string $customerName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which customer do you want to unassign automations from?'),
            $customerName
        ]);
    }

    /**
     * @Given I will choose to unassign the automations
     */
    public function iWillChooseToUnassignTheAutomations(TableNode $automationNames): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which automations do you want to unassign? (Separate multiple with commas.)'),
            $automationNames->getRow(0)
        ]);
    }

    /**
     * @Then these automations should not be assigned to the customer :customerName
     */
    public function theseAutomationsShouldNotBeAssignedToTheCustomer(string $customerName, TableNode $automationNames): void
    {
        $assignedCustomerAutomations = $this->findAssignedAutomations($customerName,
            $automationNames);

        $this->assertEmpty($assignedCustomerAutomations);
    }

    /**
     * @Then these automations should be assigned to the customer :customerName
     */
    public function theseAutomationsShouldBeAssignedToTheCustomer(string $customerName, TableNode $automationNames): void
    {
        $assignedCustomerAutomations = $this->findAssignedAutomations($customerName, $automationNames);

        $this->assertNotEmpty($assignedCustomerAutomations);
        $this->assertEquals(count($automationNames->getRows()[0]), $assignedCustomerAutomations->count());
    }

    /**
     * @Given I will choose for the automation to be assigned to the 3PL clients
     */
    public function iWillChooseForTheAutomationToBeAssignedToThePlClient(TableNode $customerNames): void
    {
        $customers = $customerNames->getRow(0);
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which ones should be included? (Case-sensitive, comma-separated, tab to auto-complete.)'),
            $customers
        ]);
    }

    /**
     * @Given I will choose not to assign to another customer when prompted
     */
    public function iWillChooseNotToAssignToAnotherCustomerWhenPrompted(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want to assign it to another customer?'),
            false
        ]);
    }

    /**
     * @Given I will choose not to add another trigger when prompted
     */
    public function iWillChooseNotToAddAnotherTriggerWhenPrompted(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want to add another condition criteria?'),
            false
        ]);
    }

    /**
     * @Given I will choose to add another :logicalOperator condition when prompted
     */
    public function iWillChooseToAddAnotherConditionWhenPrompted(string $logicalOperator): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want to add another condition criteria?'),
            true
        ]);
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which logical operator should link to the previous condition?'),
            $logicalOperator
        ]);
    }

    /**
     * @Given I will choose to add the action :actionName
     */
    public function iWillChooseToAddTheAction(string $actionName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which action do you want to add?'),
            $actionName
        ]);
    }

    /**
     * @Given I will choose to set the flag :flagName to :flagValue
     */
    public function iWillChooseToSetTheFlagTo(string $flagName, string $flagValue): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which flag do you want to set?'),
            $flagName
        ]);
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('What should the flag be set to?'),
            $flagValue
        ]);
    }

    /**
     * @Given I will choose to force adding the entire quantity
     */
    public function iWillChooseToForceIt(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Should the exact quantity be added regardless of existing line items for the same SKU?'),
            'yes'
        ]);
    }

    /**
     * @Given I will choose not to ignore cancelled
     */
    public function iWillChooseToIgnoreCancelled(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Should cancelled orders be ignored?'),
            'no'
        ]);
    }

    /**
     * @Given I will choose not to ignore fulfilled
     */
    public function iWillChooseToIgnoreFulfilled(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Should fulfilled orders be ignored?'),
            'no'
        ]);
    }

    /**
     * @Given I will choose not to force adding the entire quantity
     */
    public function iWillChooseNotToForceIt(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Should the exact quantity be added regardless of existing line items for the same SKU?'),
            'no'
        ]);
    }

    /**
     * @Given I will choose to add the SKU :sku
     */
    public function iWillChooseAddTheSKU(string $sku): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which SKU should be added?'),
            $sku
        ]);
    }

    /**
     * @Given I will choose :quantity to be added
     */
    public function iWillChooseToBeAdded(float $quantity): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('How many should be added?'),
            $quantity
        ]);
    }

    /**
     * @Given I will choose not to add an action when prompted
     */
    public function iWillChooseNotToAddAnActionWhenPrompted()
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want to add another action?'),
            false
        ]);
    }

    /**
     * @Given I save the automation when prompted
     */
    public function iSaveTheAutomationWhenPrompted()
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want to save your automation?'),
            true
        ]);
    }

    /**
     * @Given I will name the automation :automationName when prompted
     */
    public function iWillNameTheAutomationWhenPrompted(string $automationName): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('What should the automation be named?'),
            $automationName
        ]);
    }

    /**
     * @Given I will not enable the automation when prompted
     */
    public function iWillNotEnableTheAutomationWhenPrompted(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Do you want the automation to be enabled?'),
            false
        ]);
    }

    /**
     * @Given I will choose to check for manual orders
     */
    public function iWillChooseToCheckForManualOrders(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Should the order be manual?'),
            true
        ]);
    }

    /**
     * @Given I will choose the name of the customer
     */
    public function iWillChooseTheNameOftheCustomer(): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __("What should the customer's name be?"),
            true
        ]);
    }

    /**
     * @Given I will choose the ship to country :country
     */
    public function iWillChooseTheShipToCountry(string $country): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __("Which Countries the Orders should have? (Separate multiple with commas, enclose with double quotes if needed.):"),
            $country
        ]);
    }

    /**
     * @Given I will reply the question :question with :response
     */
    public function iWillReplyTheQuestionWith(string $question, string $response): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __($question),
            $response
        ]);
    }

    /**
     * @Given I will choose to have :applyTo tags matched
     */
    public function iWillChooseToHaveTagsMatched(string $applyTo): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which tags should be matched?'),
            $applyTo
        ]);
    }

    /**
     * @Given I will choose to check for the tags
     */
    public function iWillChooseToCheckForTheTags(TableNode $tagsTable)
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsQuestion', [
            __('Which tags should trigger the automation? (Separate multiple with commas.)'),
            implode(', ', $tagsTable->getRow(0))
        ]);
    }

    /**
     * @Given the command should succeed with the message :output
     */
    public function theCommandShouldSucceedWithTheMessageTheAutomationHasBeenAddedAndIsDisabled(string $output): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsOutput', [$output]);
        $this->commandFuncArray[] = self::createMethodCall('assertExitCode', [0]);
    }

    /**
     * @Given the command should fail with the message :output
     */
    public function theCommandShouldFailWithTheMessage(string $output): void
    {
        $this->commandFuncArray[] = self::createMethodCall('expectsOutput', [$output]);
        $this->commandFuncArray[] = self::createMethodCall('assertExitCode', [1]);
    }

    /**
     * @When I run the command as intended
     */
    public function iRunTheCommandAsIntended(): void
    {
        $methodCall = array_shift($this->commandFuncArray);
        $pendingCommand = call_user_func_array([$this, $methodCall->name], $methodCall->arguments);

        foreach ($this->commandFuncArray as $methodCall) {
            $pendingCommand = call_user_func_array([$pendingCommand, $methodCall->name], $methodCall->arguments);
        }
    }

    /**
     * @Then the customer :customerName should have an order automation named :automationName
     */
    public function theCustomerShouldHaveAnAutomationNamed(string $customerName, string $automationName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->automationInScope = Automation::where([
            'customer_id' => $customer->id,
            'name' => $automationName
        ])->firstOrFail();
    }

    /**
     * @Then the automation should be disabled
     */
    public function theAutomationShouldBeDisabled()
    {
        $this->automationInScope->refresh();
        $this->assertFalse($this->automationInScope->is_enabled);
    }

    /**
     * @Then the automation should be enabled
     */
    public function theAutomationShouldBeEnabled()
    {
        $this->automationInScope->refresh();
        $this->assertTrue($this->automationInScope->is_enabled);
    }

    /**
     * @Then the automation should be triggered by the :eventClass event
     */
    public function theAutomationShouldBeTriggeredByTheEvent(string $eventClass): void
    {
        $this->assertContains($eventClass, $this->automationInScope->target_events);
    }

    /**
     * @Then the automation should apply to all 3PL clients
     */
    public function theAutomationShouldApplyToAllPlClients()
    {
        $this->assertEquals(AppliesToCustomers::ALL, $this->automationInScope->applies_to);
    }

    /**
     * @Then the automation should apply to the 3PL client :customerName
     */
    public function theAutomationShouldApplyToThePlClient(string $customerName): void
    {
        $this->assertEquals(AppliesToCustomers::SOME, $this->automationInScope->applies_to);
        $this->assertContains($customerName, $this->automationInScope->appliesToCustomers->map(
            fn (Customer $customer) => $customer->contactInformation->name
        ));
    }

    /**
     * @Then the automation should not apply to the 3PL client :customerName
     */
    public function theAutomationShouldNotApplyToThePlClient(string $customerName): void
    {
        $this->assertEquals(AppliesToCustomers::NOT_SOME, $this->automationInScope->applies_to);
        $this->assertContains($customerName, $this->automationInScope->appliesToCustomers->map(
            fn (Customer $customer) => $customer->contactInformation->name
        ));
    }

    /**
     * @Then the automation should have no conditions
     */
    public function theAutomationShouldHaveNoTriggers()
    {
        $this->assertEmpty($this->automationInScope->conditions->toArray());
    }

    /**
     * @Then the automation should have a :actionName action
     */
    public function theAutomationShouldHaveAAction(string $actionName): void
    {
        $action = $this->automationInScope->actions->filter(
            fn (AutomationActionInterface $action) => $actionName == class_basename($action)
        )->first();

        $this->assertNotNull($action);
    }

    /**
     * @Then the action :actionName should be forced
     */
    public function theActionShouldBeForced(string $actionName): void
    {
        $action = $this->automationInScope->actions->filter(
            fn (AutomationActionInterface $action) => $actionName == class_basename($action)
        )->first();

        $this->assertTrue((bool) $action->force);
    }

    /**
     * @Then the action :actionName should not be forced
     */
    public function theActionShouldNotBeForced(string $actionName): void
    {
        $action = $this->automationInScope->actions->filter(
            fn (AutomationActionInterface $action) => $actionName == class_basename($action)
        )->first();

        $this->assertFalse((bool) $action->force);
    }



    /**
     * @Then the automation should have a :conditionName condition using the :logicalOperator operator
     */
    public function theAutomationShouldHaveAConditionUsingTheOperator(string $conditionName, string $logicalOperator): void
    {
        $condition = $this->automationInScope->conditions->filter(
            fn (AutomationConditionInterface $condition) => $conditionName == class_basename($condition)
        )->first();

        $this->assertNotNull($condition);
        $this->assertEquals($condition->is_alternative, $logicalOperator == 'or');
    }

    public function findAssignedAutomations(string $customerName, TableNode $automationNames): Collection
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $automationNames = $automationNames->getRow(0);
        $automationIds = Automation::query()
            ->whereIn('name', $automationNames)
            ->pluck('id')
            ->toArray();

        $assignedCustomerAutomations = DB::table('automation_applies_to_customer')
            ->whereIn('automation_id', $automationIds)
            ->where('customer_id', $customer->id)
            ->select('id')
            ->get();

        return $assignedCustomerAutomations;
    }
}
