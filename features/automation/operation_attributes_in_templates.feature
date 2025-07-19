@automation @orders
Feature: Use operation attributes as template arguments
    As a warehouse manager
    I want to use operation attributes when configuring automations
    So that I can create dynamic and versatile triggers and actions.

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

    Scenario: Adding an SKU to a manual order on standalone customer
        Given an order automation named "Add customer name tag" owned by "Test 3PL" is enabled
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        And the automation adds these tags
            | {{customer.contactInformation.name}}-pick | ships-via-{{shippingMethod.shippingCarrier.name}} |
        When the customer "Test 3PL Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-green  |
            | 1 | test-product-purple |
            | 3 | test-product-orange |
        Then the order "O-001" should have these tags
            | Co-Pilot | Test-3PL-Client-pick | ships-via-FedEx |
