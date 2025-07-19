@automation @orders
Feature: Find orders by automation actions
    As a warehouse manager
    I want to know which automations ran where
    So that I can understand which actions were performed.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49

    Scenario: Setting a flag on creation is logged into the order history
        Given an order automation named "Set some flags" owned by "Test Client" is enabled
        And the automation is triggered when an order with flag "fraud_hold" toggled "on" is received
        And the automation sets the flag "allocation_hold" to "on"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets order "O-001" with flag "fraud_hold" toggled "on"
        Then the order "O-001" should have the "allocation_hold" set to "on"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Set some flags: Added allocation hold
            """
        And the authenticated user is "roger+test_client@packiyo.com"
        And I should find order "O-001" when searching by automation "Set some flags" and event "App\Events\OrderCreatedEvent"

    Scenario: Adding a tag on update is logged into the order history
        Given an order automation named "Add tag because of product" owned by "Test Client" is enabled
        And the automation is triggered when an order is updated
        And the automation is triggered when the order has the SKU "test-product-yellow"
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        When the order "O-001" has 1 of the SKU "test-product-yellow" added to it
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |
        And I should find order "O-001" when searching by automation "Add tag because of product" and event "App\Events\OrderUpdatedEvent"

    Scenario: Order history accumulates for different target events
        Given an order automation named "Set some tags" owned by "Test Client" is enabled
        And the automation is also triggered when an order is updated
        And the automation is triggered when an order with flag "fraud_hold" toggled "on" is received
        And the automation sets the flag "allocation_hold" to "on"
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets order "O-001" with flag "fraud_hold" toggled "on"
        And the order "O-001" has 1 of the SKU "test-product-yellow" added to it
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |
        And the authenticated user is "roger+test_client@packiyo.com"
        And I should find order "O-001" when searching by automation "Set some tags" and event "App\Events\OrderCreatedEvent"
        And I should find order "O-001" when searching by automation "Set some tags" and event "App\Events\OrderUpdatedEvent"

    Scenario: Order history accumulates for different runs of the same event
        Given an order automation named "Set some tags" owned by "Test Client" is enabled
        And the automation is also triggered when an order is updated
        And the automation is triggered when an order with flag "fraud_hold" toggled "on" is received
        And the automation sets the flag "allocation_hold" to "on"
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets order "O-001" with flag "fraud_hold" toggled "on"
        And the order "O-001" has 1 of the SKU "test-product-yellow" added to it
        And the order "O-001" has 1 of the SKU "test-product-red" added to it
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |
        And the authenticated user is "roger+test_client@packiyo.com"
        And I should find order "O-001" when searching by automation "Set some tags" and event "App\Events\OrderCreatedEvent"
        And the automation should have acted 1 times on order "O-001" for event "App\Events\OrderCreatedEvent"
        And I should find order "O-001" when searching by automation "Set some tags" and event "App\Events\OrderUpdatedEvent"
        And the automation should have acted 2 times on order "O-001" for event "App\Events\OrderUpdatedEvent"
