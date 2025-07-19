@bulk_shipping @partial_bulk_shipping
Feature: Partial Bulk Shipping
    I want to be able to partially ship orders and suggest the remaining order items for a new bulk ship
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
        And the customer "Test Client" has an SKU "tv" named "TV" priced at 200
        And I manually set 100 of "tv" into "A-1" location

    Scenario: Partially shipping one order and the remaining order item product is suggested alongside other regular orders for a new bulk ship
        Given the bulk ship batch order limit is 5
        And the minimum similar orders for bulk shipping is 2
        And the instance has the feature flag "App\Features\PartialOrdersBulkShip" enabled
        And I manually set 2 of "cable" into "A-1" location
        And an order with the number "O-002" for 4 SKU "cable" is created
        And the order "O-002" has it's shipping method set to "Ground" from carrier "FedEx"
        And the order "O-002" has 3 of the SKU "door" added to it
        And the order "O-002" has 1 of the SKU "tv" added to it
        And the order "O-002" sets allow partial to "on"
        And I recalculate which orders are ready to ship
        And I start packing order "O-002"
        And I take box "Standard"
        And I pack 2 of "cable" from "A-1" location
        And I pack 3 of "door" from "A-1" location
        And I pack 1 of "tv" from "A-1" location
        And I ship the order using "generic" method
        And I manually set 100 of "cable" into "A-1" location
        And an order with the number "O-001" for 2 SKU "cable" is created
        And the order "O-001" has it's shipping method set to "Ground" from carrier "FedEx"
        And I recalculate which orders are ready to ship
        When I sync batch orders
        Then the order "O-001" should have a batch key
        And the order "O-002" should have a batch key
        And the order "O-001" should have the same batch key from the order "O-002"

    Scenario: Partially shipping one order and the remaining order item product is not suggested alongside other non-identical order for a new bulk ship
        Given the bulk ship batch order limit is 5
        And the minimum similar orders for bulk shipping is 2
        And the instance has the feature flag "App\Features\PartialOrdersBulkShip" enabled
        And I manually set 2 of "cable" into "A-1" location
        And an order with the number "O-002" for 4 SKU "cable" is created
        And the order "O-002" has it's shipping method set to "Ground" from carrier "FedEx"
        And the order "O-002" has 3 of the SKU "door" added to it
        And the order "O-002" has 1 of the SKU "tv" added to it
        And the order "O-002" sets allow partial to "on"
        And I recalculate which orders are ready to ship
        And I start packing order "O-002"
        And I take box "Standard"
        And I pack 2 of "cable" from "A-1" location
        And I pack 3 of "door" from "A-1" location
        And I pack 1 of "tv" from "A-1" location
        And I ship the order using "generic" method
        And I manually set 100 of "cable" into "A-1" location
        And an order with the number "O-001" for 3 SKU "cable" is created
        And the order "O-001" has it's shipping method set to "Ground" from carrier "FedEx"
        And I recalculate which orders are ready to ship
        When I sync batch orders
        Then the order "O-001" should have a batch key
        And the order "O-002" should have a batch key
        And the order "O-001" should have a different batch key from the order "O-002"

    Scenario: Partially shipping two orders and the remaining order items products are suggested for a new bulk ship
        Given the bulk ship batch order limit is 5
        And the minimum similar orders for bulk shipping is 2
        And the instance has the feature flag "App\Features\PartialOrdersBulkShip" enabled
        And I manually set 0 of "cable" into "A-1" location
        And an order with the number "O-001" for 4 SKU "cable" is created
        And the order "O-001" has it's shipping method set to "Ground" from carrier "FedEx"
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" sets allow partial to "on"
        And an order with the number "O-002" for 4 SKU "cable" is created
        And the order "O-002" has it's shipping method set to "Ground" from carrier "FedEx"
        And the order "O-002" has 3 of the SKU "door" added to it
        And the order "O-002" has 1 of the SKU "tv" added to it
        And the order "O-002" sets allow partial to "on"
        And I recalculate which orders are ready to ship
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I ship the order using "generic" method
        And I start packing order "O-002"
        And I take box "Standard"
        And I pack 3 of "door" from "A-1" location
        And I pack 1 of "tv" from "A-1" location
        And I ship the order using "generic" method
        And I manually set 100 of "cable" into "A-1" location
        And I recalculate which orders are ready to ship
        When I sync batch orders
        Then the order "O-001" should have a batch key
        And the order "O-002" should have a batch key
        And the order "O-001" should have the same batch key from the order "O-002"

    Scenario: Partially shipping two orders and instance does not have the feature flag enabled
        Given the bulk ship batch order limit is 5
        And the minimum similar orders for bulk shipping is 2
        And the instance has the feature flag "App\Features\PartialOrdersBulkShip" disable
        And I manually set 0 of "cable" into "A-1" location
        And an order with the number "O-001" for 4 SKU "cable" is created
        And the order "O-001" has it's shipping method set to "Ground" from carrier "FedEx"
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" sets allow partial to "on"
        And an order with the number "O-002" for 4 SKU "cable" is created
        And the order "O-002" has it's shipping method set to "Ground" from carrier "FedEx"
        And the order "O-002" has 3 of the SKU "door" added to it
        And the order "O-002" has 1 of the SKU "tv" added to it
        And the order "O-002" sets allow partial to "on"
        And I recalculate which orders are ready to ship
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I ship the order using "generic" method
        And I start packing order "O-002"
        And I take box "Standard"
        And I pack 3 of "door" from "A-1" location
        And I pack 1 of "tv" from "A-1" location
        And I ship the order using "generic" method
        And I manually set 100 of "cable" into "A-1" location
        And I recalculate which orders are ready to ship
        When I sync batch orders
        Then the order "O-001" should have a batch key
        And the order "O-002" should have a batch key
        And the order "O-001" should have a different batch key from the order "O-002"

    Scenario: Partially shipping two orders and set one of them as allow partial false after shipping
        Given the bulk ship batch order limit is 5
        And the minimum similar orders for bulk shipping is 2
        And the instance has the feature flag "App\Features\PartialOrdersBulkShip" enabled
        And I manually set 0 of "cable" into "A-1" location
        And an order with the number "O-001" for 4 SKU "cable" is created
        And the order "O-001" has it's shipping method set to "Ground" from carrier "FedEx"
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" sets allow partial to "on"
        And an order with the number "O-002" for 4 SKU "cable" is created
        And the order "O-002" has it's shipping method set to "Ground" from carrier "FedEx"
        And the order "O-002" has 3 of the SKU "door" added to it
        And the order "O-002" has 1 of the SKU "tv" added to it
        And the order "O-002" sets allow partial to "on"
        And I recalculate which orders are ready to ship
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I ship the order using "generic" method
        And I start packing order "O-002"
        And I take box "Standard"
        And I pack 3 of "door" from "A-1" location
        And I pack 1 of "tv" from "A-1" location
        And I ship the order using "generic" method
        And the order "O-001" sets allow partial to "off"
        And I manually set 100 of "cable" into "A-1" location
        And I recalculate which orders are ready to ship
        When I sync batch orders
        Then the order "O-001" should have a batch key
        And the order "O-002" should have a batch key
        And the order "O-001" should have a different batch key from the order "O-002"

