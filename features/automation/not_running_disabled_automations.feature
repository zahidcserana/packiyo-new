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
        And the customer "Test Client" has an SKU "test-product-cyan" named "Test Product Cyan" priced at 3.99
        And the customer "Test Client" has an SKU "test-product-magenta" named "Test Product Magenta" priced at 5.99
        And the customer "Test Client" has an SKU "test-product-black" named "Test Product Black" priced at 8.49

    Scenario: Not running automation when disabled
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds 1 of the SKU "test-product-red"
        And the automation is disabled
        Given an order automation named "Add gift item" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds 2 of the SKU "test-product-blue"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-red"
        Then the order "O-001" should have a line item with 1 of the SKU "test-product-red"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-blue"
