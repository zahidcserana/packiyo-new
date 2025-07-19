@automation @orders
Feature: Update an order automation
    As a warehouse manager
    I want to update an existing automation
    So that it can reflect the evolving needs of my operation.

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

    Scenario: Disabling an enabled automation
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new order has these tags
            | b2b | wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When I disable the automation
        Then the automation should be disabled
        And the automation should have 1 revisions
        And the latest audit of the automation was authored by "Roger" and logs a change in "is_enabled"
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Renaming an automation
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new order has these tags
            | b2b | wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When I rename the automation to "Add gift item"
        Then the automation should be named "Add gift item"
        And the automation should have 1 revisions
        And the latest audit of the automation was authored by "Roger" and logs a change in "name"
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Adding a target 3PL client to an automation
        Given an order automation named "Add free item" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation is triggered when a new order has these tags
            | b2b | wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        When I add the client "Another 3PL Client" to the automation
        Then the automation should apply to the following 3PL clients
            | Test 3PL Client | Another 3PL Client |
        And the automation should have 2 revisions

    Scenario: Removing a target 3PL client from an automation
        Given an order automation named "Add free item" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation applies to the 3PL client "Another 3PL Client"
        And the automation is triggered when a new order has these tags
            | b2b | wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        When I remove the client "Test 3PL Client" to the automation
        Then the automation should apply to the following 3PL clients
            | Another 3PL Client |
        And the automation should have 2 revisions
