@automation @orders
Feature: Setting shipping methods with automations over mappings
    As a warehouse manager
    I want my automations to set shipping methods overriding my mappings
    So that I can ensure the more complex and specific rules take precedence.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a shipping carrier "UPS" and a shipping method "Air"
        And the customer "Test Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49

    Scenario: Setting the shipping method over a contending mapping
        Given the channel shipping option "Two Day" is mapped to the carrier "FedEx" and the method "Ground"
        And an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the ship to country is "US"
        And the automation is triggered when the sales channel requested the "Two Day" shipping method
        And the automation sets the shipping carrier "UPS" and the shipping method "Air"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "Two Day"
        Then the order "O-001" should have the shipping carrier "UPS" and the shipping method "Air"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Set shipping method: Shipping method changed from "FedEx - Ground" to "UPS - Air"
            """
        And the authenticated user is "roger+test_client@packiyo.com"
