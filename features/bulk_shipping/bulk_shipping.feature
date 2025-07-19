@bulk_shipping
Feature: Bulk Shipping
    I want to be able to bulk ship similar orders
    So that I can save time and money

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "semir@packiyo.com" named "Semir" based in "United States"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has a shipping box named 'Standard'
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And the user "semir@packiyo.com" belongs to the customer "Test Client"
        And the user "semir@packiyo.com" is authenticated
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a location type called "Test Location Type"
        And the location "A-1" is of type "Test Location Type"
        And the customer "Test Client" has an SKU "cable" named "Cable" priced at 129.95
        And I manually set 100 of "cable" into "A-1" location
        And the customer "Test Client" has an SKU "door" named "Door" priced at 200
        And I manually set 100 of "door" into "A-1" location

    Scenario: Decreasing inventory after starting a bulk ship
        Given the customer "Test client" creates 6 orders for 1 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And I recalculate which orders are ready to ship
        When I sync batch orders
        Then I should have a bulk ship batch with 6 orders
        And I manually set 3 of "cable" into "A-1" location
        And I recalculate which orders are ready to ship
        And I sync batch orders
        Then I should have a bulk ship batch with 3 orders

    Scenario: In progress batch being suggested again with same orders
        Given the bulk ship batch order limit is 5
        And the customer "Test client" creates 9 orders for 1 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And I recalculate which orders are ready to ship
        And I sync batch orders
        And the logged user starts to ship the latest bulk ship batch
        And the logged user ships the latest bulk ship batch with limit 0
        When I sync batch orders
        And the logged user starts to ship the latest bulk ship batch
        Then I should have 2 bulk ship batches with the same key
        And these orders should belong to bulk ship batch number 1
         | ORD-1 | ORD-2 | ORD-3 | ORD-4 | ORD-5 |
        And these orders should belong to bulk ship batch number 2
         | ORD-6 | ORD-7 | ORD-8 | ORD-9 |

    Scenario: New orders after first batch
        Given the bulk ship batch order limit is 5
        And the minimum similar orders for bulk shipping is 2
        And the customer "Test client" creates 5 orders for 1 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And I recalculate which orders are ready to ship
        And I sync batch orders
        And the logged user starts to ship the latest bulk ship batch
        Then I should have a bulk ship batch with 5 orders
        And the customer "Test client" creates 2 orders for 1 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And I recalculate which orders are ready to ship
        When I sync batch orders
        Then I should have a bulk ship batch with 5 orders
        And these orders should belong to bulk ship batch number 1
         | ORD-1 | ORD-2 | ORD-3 | ORD-4 | ORD-5 |

    Scenario: Syncing bulk shipping batches with cancelled order items and feature flag disabled
        Given the bulk ship batch order limit is 5
        And the minimum similar orders for bulk shipping is 2
        And the customer "Test client" creates 5 orders for 1 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And the customer adds 1 of SKU "door" to these orders
            | ORD-1 | ORD-2 | ORD-3 | ORD-4 | ORD-5 |
        And the order items for the SKU "door" are cancelled for these orders
            | ORD-1 | ORD-2 | ORD-3 | ORD-4 | ORD-5 |
        And I recalculate which orders are ready to ship
        When I sync batch orders
        Then the customer "Test client" should have 0 bulk ship batches

    Scenario: Syncing bulk shipping batches with cancelled order items and feature flag enabled
        Given the instance has the feature flag "App\Features\AllowCancelledItemsOnBulkShip" enabled
        And the bulk ship batch order limit is 5
        And the minimum similar orders for bulk shipping is 2
        And the customer "Test client" creates 5 orders for 1 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And the customer adds 1 of SKU "door" to these orders
            | ORD-1 | ORD-2 | ORD-3 | ORD-4 | ORD-5 |
        And the order items for the SKU "door" are cancelled for these orders
            | ORD-1 | ORD-2 | ORD-3 | ORD-4 | ORD-5 |
        And I recalculate which orders are ready to ship
        When I sync batch orders
        And the logged user starts to ship the latest bulk ship batch
        Then I should have a bulk ship batch with 5 orders



