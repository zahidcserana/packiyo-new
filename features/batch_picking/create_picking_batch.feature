@picking @orders
Feature: Create a multi-item picking batch
    As a warehouse manager
    I want to pick orders with multiple items efficiently
    So that I can ship them as quickly as possible.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "A"
        And the warehouse "Test Warehouse" has 10 totes prefixed "Tot-"
        And a member user "testuser@packiyo.com" named "Test User" based in "United States"
        And the user "testuser@packiyo.com" belongs to the customer "Test 3PL"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL Client" has the setting "picking_route_strategy" set to "alphanumerically"
        And the customer "Test 3PL Client" has an SKU "test-product-1" named "Test Product 1" weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-2" named "Test Product 2" weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-3" named "Test Product 3" weighing 8.49
        And the warehouse "Test Warehouse" has 100 SKU "test-product-1" in location "A-0001"
        And the warehouse "Test Warehouse" has 100 SKU "test-product-2" in location "A-0001"
        And the warehouse "Test Warehouse" has 100 SKU "test-product-3" in location "A-0001"
        And the user "testuser@packiyo.com" is authenticated
        And the user "testuser@packiyo.com" has the setting "exclude_single_line_orders" set to "0"
        And the customer "Test 3PL Client" got the order number "O-001" for 10 SKU "test-product-1"
        And the customer "Test 3PL Client" got the order number "O-002" for 10 SKU "test-product-1"
        And the customer "Test 3PL Client" got the order number "O-005" for 10 SKU "test-product-2"
        And the order "O-005" requires 2 units of SKU "test-product-3"

    Scenario: I want to pick single order and all it's items
        Given I started picking single order "O-001"
        And the picking batch asks me to pick "test-product-1" from "A-0001" location
        And the picking batch asks me to pick "test-product-1" to "Tot-1" tote
        When I pick 10 "test-product-1" from "A-0001" location to "Tot-1" tote
        Then 10 "test-product-1" from "A-0001" location should be in "Tot-1" tote
        And the picking batch should be completed

    Scenario: I want to pick single order and skip some items
        Given I started picking single order "O-001"
        And the picking batch asks me to pick "test-product-1" from "A-0001" location
        And the picking batch asks me to pick "test-product-1" to "Tot-1" tote
        When I pick 9 "test-product-1" from "A-0001" location to "Tot-1" tote
        Then 9 "test-product-1" from "A-0001" location should be in "Tot-1" tote
        And the picking batch should be not completed

    Scenario: I want to pick multiple orders two different times
        Given a multi-item batch was created to pick 1 order
        And I recalculate which orders are ready to ship
        And the picking batch asks me to pick "test-product-1" from "A-0001" location
        And the picking batch asks me to pick "test-product-1" to "Tot-1" tote
        When I pick 10 "test-product-1" from "A-0001" location to "Tot-1" tote
        Then 10 "test-product-1" from "A-0001" location should be in "Tot-1" tote
        And the picking batch should be completed

        When a multi-item batch was created to pick 2 orders
        And the picking batch asks me to pick "test-product-1" from "A-0001" location
        And the picking batch asks me to pick "test-product-1" to "Tot-2" tote
        When I pick 10 "test-product-1" from "A-0001" location to "Tot-2" tote
        Then 10 "test-product-1" from "A-0001" location should be in "Tot-2" tote

        And the picking batch asks me to pick "test-product-2" from "A-0001" location
        And the picking batch asks me to pick "test-product-2" to "Tot-3" tote
        When I pick 10 "test-product-2" from "A-0001" location to "Tot-3" tote
        Then 10 "test-product-2" from "A-0001" location should be in "Tot-3" tote

        And the picking batch asks me to pick "test-product-3" from "A-0001" location
        And the picking batch asks me to pick "test-product-3" to "Tot-3" tote
        When I pick 2 "test-product-3" from "A-0001" location to "Tot-3" tote
        Then 2 "test-product-3" from "A-0001" location should be in "Tot-3" tote

        And the picking batch should be completed

    Scenario: Picking an order item to tote should add an audit entry
        Given I started picking single order "O-001"
        And the picking batch asks me to pick "test-product-1" from "A-0001" location
        And the picking batch asks me to pick "test-product-1" to "Tot-1" tote
        When I pick 10 "test-product-1" from "A-0001" location to "Tot-1" tote
        Then 10 "test-product-1" from "A-0001" location should be in "Tot-1" tote
        Then the app should have an audit of 'picked' event with the following message
            """
            Picked <em>10 x test-product-1</em> to tote <em>Tot-1</em> from location <em>A-0001</em>
            """
        And the picking batch should be completed

    Scenario: Clearing a tote should add an audit entry
        Given I started picking single order "O-001"
        And the picking batch asks me to pick "test-product-1" from "A-0001" location
        And the picking batch asks me to pick "test-product-1" to "Tot-1" tote
        When I pick 10 "test-product-1" from "A-0001" location to "Tot-1" tote
        Then 10 "test-product-1" from "A-0001" location should be in "Tot-1" tote
        Then I clear the tote
        Then the app should have an audit of 'removed' event with the following message
            """
            Removed <em>10 x test-product-1</em> from tote <em>Tot-1</em>
            """
        And the picking batch should be completed
