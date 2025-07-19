@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want to automate order management actions on creation
    So that I can ensure those actions are accurately and efficiently performed.

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

    Scenario: Adding an SKU to an order by sales channel on all 3PL clients
        Given an order automation named "Add free item" owned by "Test 3PL" is enabled
        And the automation applies to all 3PL clients
        And the automation is triggered when a new order from the channel "punkrock.shopify.com" is received
        And the automation adds 2 of the SKU "test-product-green"
        When the channel "punkrock.shopify.com" gets the order number "O-001" for 1 SKU "test-product-purple"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-green"

    Scenario: Adding an SKU to an order by sales channel on some 3PL clients
        Given an order automation named "Add free item" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation is triggered when a new order from the channel "punkrock.shopify.com" is received
        And the automation adds 2 of the SKU "test-product-green"
        When the channel "punkrock.shopify.com" gets the order number "O-001" for 1 SKU "test-product-purple"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-green"

    Scenario: Adding an SKU to an order by sales channel on all but some 3PL clients
        Given an order automation named "Add free item" owned by "Test 3PL" is enabled
        And the automation applies to all but the 3PL client "Another 3PL Client"
        And the automation is triggered when a new order from the channel "punkrock.shopify.com" is received
        And the automation adds 2 of the SKU "test-product-green"
        When the channel "punkrock.shopify.com" gets the order number "O-001" for 1 SKU "test-product-purple"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-green"
