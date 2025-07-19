@product
Feature: Kit components are updated and changes are synced with the pending orders
    As a customer
    I want to be able to control if order items are updated when kit components are changed
    So I can properly manage kit products

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test Client" has an SKU "test-product-blue-addition" named "Test Product Blue  Addition" priced at 3.99
        And the warehouse "Test Warehouse" had quantity 30 that attached in product with location "A-1"
        And the customer "Test Client" has an SKU "test-product-blue-kit" named "Test Product Blue Kit" priced at 3.99
        And the customer "Test Client" got the order number "O-001" for 10 SKU "test-product-blue-kit"

Scenario: Changing product type to kit, adding a component and making sure orders stay the same
    When the user "roger+test_client@packiyo.com" is authenticated
    And the order number "O-001" has item count 1
    Then the product type is regular
    And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
    Then the product type is kit
    And the order number "O-001" has item count 1

Scenario: Changing product type to kit, adding a component and updating pending orders
    When the user "roger+test_client@packiyo.com" is authenticated
    And the customer "Test Client" got the order number "O-002" for 5 SKU "test-product-blue-kit"
    Then the order number "O-001" has item count 1
    Then the order number "O-002" has item count 1
    Then the product type is regular
    And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
    Then the product type is kit
    When the kit product is synced with pending order items
    # Order O-001
    Then the order number "O-001" with kit order item SKU "test-product-blue-kit" has 1 component lines
    And the order number "O-001" should have SKU "test-product-blue" with quantity 20
    And the order number "O-001" has item count 2
    # Order O-002
    And the order number "O-002" with kit order item SKU "test-product-blue-kit" has 1 component lines
    And the order number "O-002" should have SKU "test-product-blue" with quantity 10
    And the order number "O-002" has item count 2

Scenario: Removing component from a kit
    When the user "roger+test_client@packiyo.com" is authenticated
    And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
    And the SKU "test-product-blue-addition" is added as a component to the kit product with quantity of 3
    Then the product type is kit
    When the customer "Test Client" got the order number "O-002" for 5 SKU "test-product-blue-kit"
    And the component SKU "test-product-blue-addition" is removed from the kit product
    Then the order number "O-002" with kit order item SKU "test-product-blue-kit" has 2 component lines
    And the order number "O-002" should have SKU "test-product-blue" with quantity 10
    And the order number "O-002" should have SKU "test-product-blue-addition" with quantity 15
    And the order number "O-002" has item count 3

Scenario: Removing component from a kit and updating pending orders
    When the user "roger+test_client@packiyo.com" is authenticated
    And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
    And the SKU "test-product-blue-addition" is added as a component to the kit product with quantity of 3
    Then the product type is kit
    When the customer "Test Client" got the order number "O-002" for 5 SKU "test-product-blue-kit"
    And the component SKU "test-product-blue-addition" is removed from the kit product
    And the kit product is synced with pending order items
    Then the order number "O-002" with kit order item SKU "test-product-blue-kit" has 2 component lines
    And the order number "O-002" should have SKU "test-product-blue" with quantity 10
    And the order number "O-002" should have cancelled SKU "test-product-blue-addition" with quantity 15
    And the order number "O-002" has item count 3

Scenario: Removing component from a kit and updating pending orders
    When the user "roger+test_client@packiyo.com" is authenticated
    And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
    And the SKU "test-product-blue-addition" is added as a component to the kit product with quantity of 3
    Then the product type is kit
    When the customer "Test Client" got the order number "O-002" for 5 SKU "test-product-blue-kit"
    And the component SKU "test-product-blue-addition" is removed from the kit product
    And the kit product is synced with pending order items
    Then the order number "O-002" with kit order item SKU "test-product-blue-kit" has 2 component lines
    And the order number "O-002" should have SKU "test-product-blue" with quantity 10
    And the order number "O-002" should have cancelled SKU "test-product-blue-addition" with quantity 15
    And the order number "O-002" has item count 3

Scenario: Updating components and saving already fulfilled order should not change any lines
    When the user "roger+test_client@packiyo.com" is authenticated
    And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
    And the kit product is synced with pending order items
    And the order "O-001" is marked as fulfilled
    And the SKU "test-product-blue-addition" is added as a component to the kit product with quantity of 3
    And the kit product is synced with pending order items
    Then the order number "O-001" has item count 2
