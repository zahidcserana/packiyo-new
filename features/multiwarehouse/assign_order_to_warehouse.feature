@multiwarehouse
Feature: Assign order to a warehouse
    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "jonah@packiyo.com" named "Jonah" based in "United States"
        And the warehouse "Test Warehouse" belongs to customer "Test Client"
        And the user "jonah@packiyo.com" belongs to the customer "Test Client"
        And the user "jonah@packiyo.com" is authenticated
        And a member user "judah@packiyo.com" named "Judah" based in "United States"
        And the warehouse "Test Warehouse 2" belongs to customer "Test Client"
        And the user "judah@packiyo.com" belongs to the customer "Test Client"
        And the user "judah@packiyo.com" is authenticated
        And an order with number "ORD-1" that belongs to customer "Test Client"
        And the order "ORD-1" has 3 products with SKU "PROD-1"
        And the order "ORD-1" has 1 products with SKU "PROD-2"
        And an order with number "ORD-2" that belongs to customer "Test Client"
        And the order "ORD-2" has 10 products with SKU "PROD-2"
        And an order with number "ORD-3" that belongs to customer "Test Client"
        And the order "ORD-3" has 11 products with SKU "PROD-1"
        And the warehouse "Test Warehouse" has 5 locations of type "A"
        And the warehouse "Test Warehouse 2" has 5 locations of type "A"
        And the warehouse "Test Warehouse" has 100 SKU "PROD-1" in location "A-0001"
        And the warehouse "Test Warehouse 2" has 100 SKU "PROD-1" in location "A-0001"
        And the warehouse "Test Warehouse" has 100 SKU "PROD-2" in location "A-0001"
        And the warehouse "Test Warehouse 2" has 100 SKU "PROD-2" in location "A-0001"
        And I allocate the inventory for "PROD-1" in all warehouses for customer "Test Client"
        And I allocate the inventory for "PROD-2" in all warehouses for customer "Test Client"

    Scenario: Assign the order to a warehouse
        Given I assign the order "ORD-1" to the warehouse "Test Warehouse"
        And the user "jonah@packiyo.com" is authenticated
        Then order with number "ORD-1" has a warehouse assigned to it

    Scenario: Picking batch should only get the orders from the warehouse the user is assigned to
        Given the user "jonah@packiyo.com" is authenticated
        And I assign the order "ORD-1" to the warehouse "Test Warehouse"
        And I assign the order "ORD-2" to the warehouse "Test Warehouse 2"
        And I assign the order "ORD-3" to the warehouse "Test Warehouse"
        And I recalculate which orders are ready to ship
        And a multi-item batch was created to pick 3 orders
        Then the picking batch contains items from the following orders "ORD-1,ORD-3"
        And the warehouse "Test Warehouse" is assigned to the picking batch
