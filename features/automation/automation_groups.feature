@automation @orders
Feature: Run only the first matching automation in a group
    As a warehouse manager
    I want to have a single automation of a group
    So that I can configure alternative automations.

    Background:
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

    Scenario: Running only the first matching automation of a group
        Given an order automation named "Add tags A" owned by "Test 3PL" is enabled
        And the automation is triggered when a new order has these tags
            | first-trigger-a | second-trigger-a |
        And the automation adds these tags
            | first-action-a | second-action-a |
        Given an order automation named "Add tags B" owned by "Test 3PL" is enabled
        And the automation is triggered when a new order has these tags
            | first-trigger-b | second-trigger-b |
        And the automation adds these tags
            | first-action-b | second-action-b |
        Given an order automation named "Add tags C" owned by "Test 3PL" is enabled
        And the automation is triggered when a new order has these tags
            | first-trigger-b | second-trigger-b |
        And the automation adds these tags
            | first-action-c | second-action-c |
        Given an order automation named "Add tags D" owned by "Test 3PL" is enabled
        And the automation is triggered when a new order has these tags
            | first-trigger-b | second-trigger-b |
        And the automation adds these tags
            | first-action-d | second-action-d |
        And an automation group named "Adding some tags" owned by "Test 3PL" is enabled
        # And the automation group applies to all 3PL clients
        And the automation applies to all 3PL clients
        And the automation group includes the automation "Add tags A"
        And the automation group includes the automation "Add tags B"
        And the automation group includes the automation "Add tags C"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the channel "heavymetal.shopify.com" gets the order number "O-001" with these tags
            | first-trigger-b | second-trigger-b |
        Then the order "O-001" should have these tags
            | Co-Pilot | first-action-b | first-action-d | first-trigger-b | second-action-b | second-action-d | second-trigger-b |
        And the order "O-001" has a log entry by "Roger" that reads
            """
            Added "first-trigger-b, second-trigger-b" tags
            """
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add tags B: Added "first-action-b, second-action-b" tags
            """
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add tags D: Added "first-action-d, second-action-d" tags
            """
        And the authenticated user is "roger+test_3pl@packiyo.com"
