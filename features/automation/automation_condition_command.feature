@automation @cli @internal
Feature: Automation CLI
    As a Packiyo staffer
    I want to be able to create automations quickly
    So that I can onboard clients and support them.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        # 3PL and 3PL client
        And a 3PL called "Test 3PL" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49

    Scenario: Creating a basic automation for a 3PL client that forces the addition of a line item
        Given I will run the "automation:create" command
        And I will choose for the automation to be owned by the customer "Test 3PL"
        And I will choose for the automation to apply to the 3PL client "Test 3PL Client"
        And I will choose "App\Events\OrderCreatedEvent" as the only triggering event
        And I will choose not to add a condition when prompted
        And I will choose to add the action "AddLineItemAction"
        And I will choose to add the SKU "test-product-blue"
        And I will choose 5 to be added
        And I will choose to force adding the entire quantity
        And I will choose not to ignore cancelled
        And I will choose not to ignore fulfilled
        And I will choose not to add an action when prompted
        And I save the automation when prompted
        And I will name the automation "Force AddLineItem" when prompted
        And I will not enable the automation when prompted
        And the command should succeed with the message "Your automation was saved."
        When I run the command as intended
        Then the customer "Test 3PL" should have an order automation named "Force AddLineItem"
        And the automation should be disabled
        And the automation should be triggered by the "App\Events\OrderCreatedEvent" event
        And the automation should apply to the 3PL client "Test 3PL Client"
        And the automation should have a "AddLineItemAction" action
        And the action "AddLineItemAction" should be forced

    Scenario: Creating a basic automation for a 3PL client that does not force the addition of a line item
        Given I will run the "automation:create" command
        And I will choose for the automation to be owned by the customer "Test 3PL"
        And I will choose for the automation to apply to the 3PL client "Test 3PL Client"
        And I will choose "App\Events\OrderCreatedEvent" as the only triggering event
        And I will choose not to add a condition when prompted
        And I will choose to add the action "AddLineItemAction"
        And I will choose to add the SKU "test-product-blue"
        And I will choose 5 to be added
        And I will choose not to force adding the entire quantity
        And I will choose not to ignore cancelled
        And I will choose not to ignore fulfilled
        And I will choose not to add an action when prompted
        And I save the automation when prompted
        And I will name the automation "Force AddLineItem" when prompted
        And I will not enable the automation when prompted
        And the command should succeed with the message "Your automation was saved."
        When I run the command as intended
        Then the customer "Test 3PL" should have an order automation named "Force AddLineItem"
        And the automation should be disabled
        And the automation should be triggered by the "App\Events\OrderCreatedEvent" event
        And the automation should apply to the 3PL client "Test 3PL Client"
        And the automation should have a "AddLineItemAction" action
        And the action "AddLineItemAction" should not be forced
