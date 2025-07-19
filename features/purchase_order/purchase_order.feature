@purchase_order
Feature: Import purchase orders
    As a merchant
    I want to create or update purchase orders with public API

Background:
    Given a customer called "Test Client" based in "United States"
    And an admin user "roger+test_client@packiyo.com" named "Roger" based in "United States"
    And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
    And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
    And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" priced at 49.99
    And the warehouse "Test Warehouse" had quantity 30 that attached in product with location "A-1"
    And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 39.99
    And the warehouse "Test Warehouse" had quantity 20 that attached in product with location "A-1"
    And the customer "Test Client" has a supplier "Supplier 1"
    And the user "roger+test_client@packiyo.com" is authenticated

    Scenario: the customer adds a product to a purchase order
        Given the customer has a purchase order with tracking number "PO-1" for the warehouse "Test Warehouse"
        And the purchase order has a product "test-product-red" with quantity 10
        And the customer starts editing the purchase order
        And the customer adds a product "test-product-blue" with quantity 5 to the purchase order
        When the customer finishes editing the purchase order
        Then the purchase order should have the product "test-product-red" with quantity 10
        And the purchase order should have the product "test-product-blue" with quantity 5

    Scenario: update purchase order's quantity and quantity received where quantity is less than quantity received
        Given the customer has a purchase order with tracking number "PO-1" for the warehouse "Test Warehouse"
        And the purchase order has a product "test-product-red" with quantity 10
        And the customer starts editing the purchase order
        And the customer changes the quantity of the product "test-product-red" to 5
        And the customer changes the quantity received of the product "test-product-red" to 6
        When the customer finishes editing the purchase order
        Then the response error with errors field "purchase_order_items.0.quantity" contains error message
            """
            The purchase_order_items.0.quantity must be greater than or equal 6.
            """
        Then the response code is "422"

    Scenario: update purchase order's quantity where quantity is less then original received quantity
        Given the customer has a purchase order with tracking number "PO-1" for the warehouse "Test Warehouse"
        And the purchase order has a product "test-product-red" with quantity 100
        And the SKU "test-product-red" on the purchase order "PO-1" has quantity received of 200
        And the customer starts editing the purchase order
        And the customer changes the quantity of the product "test-product-red" to 150
        And the customer changes the quantity received of the product "test-product-red" to 200
        When the customer finishes editing the purchase order
        Then the response error with errors field "purchase_order_items.0.quantity" contains error message
            """
            The purchase_order_items.0.quantity must be greater than or equal 200.
            """
        Then the response code is "422"

    Scenario: update purchase order's sell ahead with original quantity less then original received quantity
        Given the customer has a purchase order with tracking number "PO-1" for the warehouse "Test Warehouse"
        And the purchase order has a product "test-product-red" with quantity 100
        And the SKU "test-product-red" on the purchase order "PO-1" has quantity received of 200
        And the customer starts editing the purchase order
        And the customer changes the quantity of the product "test-product-red" to 100
        And the customer changes the quantity received of the product "test-product-red" to 200
        And the customer changes the sell ahead quantity of the product "test-product-red" to 50
        When the customer finishes editing the purchase order
        Then the response code is "200"
        And the purchase order "PO-1" item "test-product-red" should contains in field "quantity" a value "100"
        And the purchase order "PO-1" item "test-product-red" should contains in field "quantity_sell_ahead" a value "50"

    Scenario: update purchase order's sell ahead and quantity where quantity is equal to original received quantity
        Given the customer has a purchase order with tracking number "PO-1" for the warehouse "Test Warehouse"
        And the purchase order has a product "test-product-red" with quantity 100
        And the SKU "test-product-red" on the purchase order "PO-1" has quantity received of 200
        And the customer starts editing the purchase order
        And the customer changes the quantity of the product "test-product-red" to 200
        And the customer changes the quantity received of the product "test-product-red" to 200
        And the customer changes the sell ahead quantity of the product "test-product-red" to 50
        When the customer finishes editing the purchase order
        Then the response code is "200"
        And the purchase order "PO-1" item "test-product-red" should contains in field "quantity" a value "200"
        And the purchase order "PO-1" item "test-product-red" should contains in field "quantity_sell_ahead" a value "50"
