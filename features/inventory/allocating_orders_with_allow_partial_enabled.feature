@inventory @allow_partial_orders
Feature: Allocating inventory for orders that have allow partial enabled

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has a pickable location called "A-0001"
        And the customer "Test Client" has an SKU "SKU-1" named "Test Product 1" weighing 3.99
        And the customer "Test Client" has an SKU "SKU-2" named "Test Product 2" weighing 4.99
        And the customer "Test Client" has an SKU "SKU-3" named "Test Product 3" weighing 5.99
        And the warehouse "Test Warehouse" has 10 SKU "SKU-1" in location "A-0001"
        And the warehouse "Test Warehouse" has 10 SKU "SKU-2" in location "A-0001"
        And the warehouse "Test Warehouse" has 0 SKU "SKU-2" in location "A-0001"
        And the queues are turned off

    Scenario: Recalculate ready to ship for order that didn't allocate all products
        Given the customer "Test Client" got the order number "ORD-1" for 5 SKU "SKU-1"
        And the order "ORD-1" has 5 of the SKU "SKU-2" added to it
        And the order "ORD-1" has 5 of the SKU "SKU-3" added to it
        And the order "ORD-1" has field "allow_partial" set to 1
        And I allocate the SKU "SKU-1"
        Then I recalculate orders that are ready to ship
        And the order "ORD-1" should have the "ready_to_ship" set to "off"

    Scenario: Recalculate ready to ship for order that allocated all products
        Given the customer "Test Client" got the order number "ORD-2" for 5 SKU "SKU-1"
        And the order "ORD-2" has 5 of the SKU "SKU-2" added to it
        And the order "ORD-2" has 5 of the SKU "SKU-3" added to it
        And the order "ORD-2" has field "allow_partial" set to 1
        And I allocate the SKU "SKU-1"
        And I allocate the SKU "SKU-2"
        And I allocate the SKU "SKU-3"
        Then I recalculate orders that are ready to ship
        And the order "ORD-2" should have the "ready_to_ship" set to "on"

