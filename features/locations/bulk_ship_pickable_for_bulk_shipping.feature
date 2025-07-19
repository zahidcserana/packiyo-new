@location @bulk_ship_pickable
Feature: Use only bulk ship pickable locations for Bulk shipping

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "semir@packiyo.com" named "Semir" based in "United States"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has a shipping box named 'Standard'
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And the user "semir@packiyo.com" belongs to the customer "Test Client"
        And the user "semir@packiyo.com" is authenticated
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a pickable location called "A-2"
        And the warehouse "Test Warehouse" has a pickable location called "A-3"
        And the warehouse "Test Warehouse" has a location type called "Test Location Type"
        And the location "A-3" is of type "Test Location Type"
        And the customer "Test Client" has an SKU "cable" named "Cable" priced at 129.95
        And I manually set 100 of "cable" into "A-1" location
        And I manually set 100 of "cable" into "A-2" location
        And I manually set 100 of "cable" into "A-3" location
        And an order with the number "O-001" for 5 SKU "cable" is created
        And I recalculate which orders are ready to ship

    Scenario: Set location to have only bulk ship pickable attribute
        Given the customer "Test Client" updates the setting "only_use_bulk_ship_pickable_locations" set to true
        And the location type "Test Location Type" has bulk ship pickable set to 1
        And the customer "Test client" creates 10 orders for 5 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And I recalculate which orders are ready to ship
        And I sync batch orders
        Then I should have a bulk ship batch with 10 orders
        And I should have 1 bulk ship pickable locations
        When the customer "Test Client" updates the setting "only_use_bulk_ship_pickable_locations" set to false
        Then I should have 3 bulk ship pickable locations
        When the location type "Test Location Type" has bulk ship pickable set to 0
        And the customer "Test Client" updates the setting "only_use_bulk_ship_pickable_locations" set to true
        Then I should have 0 bulk ship pickable locations

    Scenario: Showing the bulk ship PDF with orders shipped amount
        Given the customer "Test Client" updates the setting "only_use_bulk_ship_pickable_locations" set to true
        And the location type "Test Location Type" has bulk ship pickable set to 1
        And the customer "Test client" creates 3 orders for 1 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And I recalculate which orders are ready to ship
        And I sync batch orders
        And the user "semir@packiyo.com" is authenticated
        And the logged user starts to ship the bulk ship batch
        When the logged user ships the bulk ship batch
        Then the bulk ship PDF should show orders shipped amount as 3

    Scenario: Ensure uniqueness of bulk ship batch order rows
        Given the customer "Test Client" updates the setting "only_use_bulk_ship_pickable_locations" set to true
        And the location type "Test Location Type" has bulk ship pickable set to 1
        And the customer "Test client" creates 3 orders for 1 SKU "cable" with shipping method from carrier "FedEx" set to "Ground"
        And I recalculate which orders are ready to ship
        And I sync batch orders
        And the user "semir@packiyo.com" is authenticated
        When the logged user starts to ship the bulk ship batch
        Then it shouldn't be possible to create duplicate order rows in the bulk ship batch




