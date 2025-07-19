@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want to automate order management actions on creation
    So that I can ensure those actions are accurately and efficiently performed.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49
        # 3PL and 3PL client
        And a 3PL called "Test 3PL" based in "United States"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a sales channel named "punkrock.shopify.com"
        And the customer "Test 3PL Client" has an SKU "test-product-green" named "Test Product Green" weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-orange" named "Test Product Orange" weighing 8.49
        And a customer called "Another 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Another 3PL Client" has a sales channel named "heavymetal.shopify.com"

    Scenario: Raising the priority of an order when it turns 20 hours old
        Given an order automation named "Raise priority on old orders" owned by "Test Client" is enabled
        And the automation is triggered when an order turns 20 "hours" old
        And the automation sets the flag "priority" to "on"
        And the customer "Test Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order number "O-001" was created 21 "hours" ago
        And the order "O-001" should have the "priority" set to "off"
        When a cron runs the timed automations
        Then the order "O-001" should have the "priority" set to "on"

    Scenario: Not raising the priority of an order when it's not yet 20 hours old
        Given an order automation named "Raise priority on old orders" owned by "Test Client" is enabled
        And the automation is triggered when an order turns 20 "hours" old
        And the automation sets the flag "priority" to "on"
        And the customer "Test Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order number "O-001" was created 19 "hours" ago
        And the order "O-001" should have the "priority" set to "off"
        When a cron runs the timed automations
        Then the order "O-001" should have the "priority" set to "off"

    Scenario: Not running timed automations twice even though the condition is still met
        Given an order automation named "Raise priority on old orders" owned by "Test Client" is enabled
        And the automation is triggered when an order turns 20 "hours" old
        And the automation sets the flag "priority" to "on"
        And the automation adds 3 of the SKU "test-product-blue"
        And the customer "Test Client" got the order number "O-001" for 2 SKU "test-product-blue"
        And the order number "O-001" was created 21 "hours" ago
        And the order "O-001" should have the "priority" set to "off"
        When a cron runs the timed automations
        And a cron runs the timed automations
        And a cron runs the timed automations
        Then the order "O-001" should have the "priority" set to "on"
        And the order "O-001" should have a line item with 3 of the SKU "test-product-blue"

    Scenario: Not running timed automations twice even though the condition is still met on edited automations
        Given an order automation named "Raise priority on old orders" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation is triggered when an order turns 20 "hours" old
        And the automation sets the flag "priority" to "on"
        And the automation adds 3 of the SKU "test-product-green"
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-green"
        And the order number "O-001" was created 21 "hours" ago
        And the order "O-001" should have the "priority" set to "off"
        When a cron runs the timed automations
        And I add the client "Another 3PL Client" to the automation
        And a cron runs the timed automations
        Then the order "O-001" should have the "priority" set to "on"
        And the order "O-001" should have a line item with 3 of the SKU "test-product-green"

    Scenario: Raising the priority of an order when it turns 5 business days old
        Given an order automation named "Raise priority on old orders" owned by "Test Client" is enabled
        And the automation is triggered when an order turns 5 "business_days" old
        And the automation sets the flag "priority" to "on"
        And the customer "Test Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order number "O-001" was created 6 "business_days" ago
        And the order "O-001" should have the "priority" set to "off"
        When a cron runs the timed automations
        Then the order "O-001" should have the "priority" set to "on"

    Scenario: Running one of two automations using order aged events
        Given an order automation named "Tag order when 20 hours old" owned by "Test Client" is enabled
        And the automation is triggered when an order turns 20 "hours" old
        And the automation adds these tags
            | 20-hours-old |
        And an order automation named "Tag order when 40 hours old" owned by "Test Client" is enabled
        And the automation is triggered when an order turns 40 "hours" old
        And the automation adds these tags
            | 40-hours-old |
        And the customer "Test Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order number "O-001" was created 20 "hours" ago
        When a cron runs the timed automations
        Then the order "O-001" should have these tags
            | 20-hours-old | Co-Pilot |
