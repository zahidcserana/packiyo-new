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
        And the customer "Test Client" has a sales channel named "punkrock.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49
        And the customer "Test Client" has an SKU "test-product-cyan" named "Test Product Cyan" priced at 3.99
        And the customer "Test Client" has an SKU "test-product-magenta" named "Test Product Magenta" priced at 5.99
        And the customer "Test Client" has an SKU "test-product-black" named "Test Product Black" priced at 8.49

    Scenario: Trigger when one of two alternative criteria matches
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has a line item with these tags
            | b2b | wholesale |
        And the conditions are alternatives to each other
        And the automation adds 2 of the SKU "test-product-yellow"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-yellow"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-yellow"

    Scenario: Trigger with alternative criteria and non-alternative criteria
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has a line item with these tags
            | b2b | wholesale |
        And the conditions are alternatives to each other
        And the automation is triggered when the field "number" on an order "matches" the pattern "{@}-{#+}"
        And the automation is triggered when the order has a total of 2 items
        And the automation adds 3 of the SKU "test-product-yellow"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 2 SKU "test-product-yellow"
        Then the order "O-001" should have a line item with 3 of the SKU "test-product-yellow"

    Scenario: Don't trigger with alternative criteria and non-alternative criteria
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "punkrock.shopify.com" is received
        And the automation is triggered when a new order has a line item with these tags
            | b2b | wholesale |
        And the conditions are alternatives to each other
        And the automation is triggered when the field "number" on an order "matches" the pattern "{@}-{#+}"
        And the automation is triggered when the order has a total of 2 items
        And the automation adds 1 of the SKU "test-product-yellow"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 2 SKU "test-product-yellow"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-yellow"
