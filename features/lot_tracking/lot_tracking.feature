@lot_tracking
Feature: Lot tracking on products
    As the warehouse manager
    I want to be able to track lot information on products and locations
    So that I can keep track of inventory that is about to expire

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL" has a shipping box named 'Standard'
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a pickable location called "A-2"
        And the warehouse "Test Warehouse" has a pickable location called "A-3"
        And the warehouse "Test Warehouse" has a pickable location called "A-4"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "sku-1" named "Product 1" priced at 3.99
        And the product has a supplier called "Supplier 1"
        And the customer "Test 3PL Client" has an SKU "sku-2" named "Product 2" priced at 3.99
        And the product has a supplier called "Supplier 1"
        And the customer "Test 3PL Client" has an SKU "sku-with-lot-tracking-1" named "Lot Product 1" priced at 3.99
        And the product has a supplier called "Supplier 1"
        And the product has lot tracking set to 1
        And the product has lot called "P1-L1" expiring on "2024-12-01" from supplier "Supplier 1"
        And the product has lot called "P1-L2" expiring on "2023-12-01" from supplier "Supplier 1"
        And the product has lot called "P1-L3" expiring on "2025-12-01" from supplier "Supplier 1"
        And the product has lot called "P1-L4" expiring on "2023-12-01" from supplier "Supplier 1"
        And the customer "Test 3PL Client" has an SKU "sku-with-lot-tracking-2" named "Lot Product 2" priced at 3.99
        And the product has a supplier called "Supplier 1"
        And the product has lot tracking set to 1
        And the product has lot called "P2-L1" expiring on "2023-12-01" from supplier "Supplier 1"
        And the product has lot called "P2-L2" expiring on "2024-12-01" from supplier "Supplier 1"
        And the client "Test 3PL Client" has a pending purchase order "PO-1" from the supplier "Supplier 1" for 100 of the SKU "sku-1"
        And the purchase order requires "200" of the SKU "sku-2"
        And the purchase order requires "300" of the SKU "sku-with-lot-tracking-1"
        And the purchase order requires "400" of the SKU "sku-with-lot-tracking-2"

    Scenario: Receiving lot tracked and lot untracked products with multiple lots into a receiving location
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 10 of "sku-1" into "Receiving" location
        And I receive 1 of "sku-1" into "Receiving" location
        Then the product should have inventory 11 on location "Receiving"
        When I receive 20 of "sku-2" into "Receiving" location
        When I receive 1 of "sku-2" into "Receiving" location
        Then the product should have inventory 21 on location "Receiving"
        When I receive 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "Receiving" location
        When I receive 1 of "sku-with-lot-tracking-1" with lot "P1-L2" into "Receiving" location
        Then the product should have inventory 31 on location "Receiving"
        And the product lot report should show added 30, removed 0 and remaining 30 for lot "P1-L1" on location "Receiving"
        And the product lot report should show added 1, removed 0 and remaining 1 for lot "P1-L2" on location "Receiving"
        When I receive 40 of "sku-with-lot-tracking-2" with lot "P2-L1" into "Receiving" location
        When I receive 1 of "sku-with-lot-tracking-2" with lot "P2-L2" into "Receiving" location
        Then the product should have inventory 41 on location "Receiving"
        And the product lot report should show added 40, removed 0 and remaining 40 for lot "P2-L1" on location "Receiving"
        And the product lot report should show added 1, removed 0 and remaining 1 for lot "P2-L2" on location "Receiving"

    Scenario: Receiving lot untracked products into one non-receiving location
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 10 of "sku-1" into "A-1" location
        And I receive 5 of "sku-1" into "A-1" location
        Then the product should have inventory 15 on location "A-1"
        When I receive 20 of "sku-2" into "A-1" location
        When I receive 5 of "sku-2" into "A-1" location
        Then the product should have inventory 25 on location "A-1"

    Scenario: Receiving lot tracked and lot untracked products with multiple lots into different locations
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 10 of "sku-1" into "A-1" location
        And I receive 5 of "sku-1" into "A-1" location
        Then the product should have inventory 15 on location "A-1"
        When I receive 20 of "sku-2" into "A-2" location
        When I receive 5 of "sku-2" into "A-2" location
        Then the product should have inventory 25 on location "A-2"
        When I receive 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-3" location
        When I receive 5 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-3" location
        Then the product should have inventory 35 on location "A-3"
        And the product lot report should show added 35, removed 0 and remaining 35 for lot "P1-L1" on location "A-3"
        When I receive 40 of "sku-with-lot-tracking-2" with lot "P2-L1" into "A-4" location
        When I receive 5 of "sku-with-lot-tracking-2" with lot "P2-L1" into "A-4" location
        Then the product should have inventory 45 on location "A-4"
        And the product lot report should show added 45, removed 0 and remaining 45 for lot "P2-L1" on location "A-4"

    Scenario: Receiving a product with different lots to same non-receiving location should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        Then I shouldn't be able to receive 5 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-1" location
        And the product should have inventory 30 on location "A-1"
        And the product lot report should show added 30, removed 0 and remaining 30 for lot "P1-L1" on location "A-1"

    Scenario: Receiving a lot tracked product into non-receiving location which already has lot untracked product should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 10 of "sku-1" into "A-1" location
        Then the product should have inventory 10 on location "A-1"
        And I shouldn't be able to receive 10 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And the product lot report should show nothing for lot "P1-L1" on location "A-1"

    Scenario: Receiving a lot untracked product into non-receiving location which already has lot tracked product should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        Then the product should have inventory 30 on location "A-1"
        And the product lot report should show added 30, removed 0 and remaining 30 for lot "P1-L1" on location "A-1"
        And I shouldn't be able to receive 10 of "sku-1" into "A-1" location

    Scenario: Receiving a lot tracked product into non-receiving location which already has lot tracked product should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        Then the product should have inventory 30 on location "A-1"
        And the product lot report should show added 30, removed 0 and remaining 30 for lot "P1-L1" on location "A-1"
        And I shouldn't be able to receive 10 of "sku-with-lot-tracking-2" with lot "P2-L1" into "A-1" location
        And the product lot report should show nothing for lot "P2-L1" on location "A-1"

    Scenario: Receiving lot tracked product to location without providing lot information should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        Then I shouldn't be able to receive 10 of "sku-with-lot-tracking-2" into "A-1" location

    Scenario: Manually adding lot tracked and lot untracked products with multiple lots into a receiving location
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 11 of "sku-1" into "Receiving" location
        Then the product should have inventory 11 on location "Receiving"
        When I manually set 21 of "sku-2" into "Receiving" location
        Then the product should have inventory 21 on location "Receiving"
        When I manually set 31 of "sku-with-lot-tracking-1" with lot "P1-L1" into "Receiving" location
        Then the product should have inventory 31 on location "Receiving"
        And the product lot report should show added 31, removed 0 and remaining 31 for lot "P1-L1" on location "Receiving"
        When I manually set 41 of "sku-with-lot-tracking-1" with lot "P1-L2" into "Receiving" location
        Then the product should have inventory 72 on location "Receiving"
        And the product lot report should show added 41, removed 0 and remaining 41 for lot "P1-L2" on location "Receiving"
        When I manually set 51 of "sku-with-lot-tracking-2" with lot "P2-L1" into "Receiving" location
        Then the product should have inventory 51 on location "Receiving"
        And the product lot report should show added 51, removed 0 and remaining 51 for lot "P2-L1" on location "Receiving"
        When I manually set 61 of "sku-with-lot-tracking-2" with lot "P2-L2" into "Receiving" location
        Then the product should have inventory 112 on location "Receiving"
        And the product lot report should show added 61, removed 0 and remaining 61 for lot "P2-L2" on location "Receiving"

    Scenario: Manually adding lot untracked products into one non-receiving location
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 10 of "sku-1" into "A-1" location
        And I manually set 5 of "sku-1" into "A-1" location
        Then the product should have inventory 5 on location "A-1"
        When I manually set 20 of "sku-2" into "A-1" location
        When I manually set 5 of "sku-2" into "A-1" location
        Then the product should have inventory 5 on location "A-1"

    Scenario: Manually adding lot tracked and lot untracked products with multiple lots into different locations
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 10 of "sku-1" into "A-1" location
        And I manually set 5 of "sku-1" into "A-1" location
        Then the product should have inventory 5 on location "A-1"
        When I manually set 20 of "sku-2" into "A-2" location
        When I manually set 5 of "sku-2" into "A-2" location
        Then the product should have inventory 5 on location "A-2"
        When I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-3" location
        When I manually set 5 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-3" location
        Then the product should have inventory 5 on location "A-3"
        And the product lot report should show added 30, removed 25 and remaining 5 for lot "P1-L1" on location "A-3"
        When I manually set 40 of "sku-with-lot-tracking-2" with lot "P2-L1" into "A-4" location
        When I manually set 5 of "sku-with-lot-tracking-2" with lot "P2-L1" into "A-4" location
        Then the product should have inventory 5 on location "A-4"
        And the product lot report should show added 40, removed 35 and remaining 5 for lot "P2-L1" on location "A-4"

    Scenario: Manually adding a product with different lots to same non-Manually adding location should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        Then I shouldn't be able to manually set 5 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-1" location
        And the product should have inventory 30 on location "A-1"
        And the product lot report should show added 30, removed 0 and remaining 30 for lot "P1-L1" on location "A-1"

    Scenario: Manually adding a lot tracked product into non-receiving location which already has lot untracked product should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 10 of "sku-1" into "A-1" location
        Then the product should have inventory 10 on location "A-1"
        And I shouldn't be able to manually set 10 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And the product lot report should show nothing for lot "P1-L1" on location "A-1"

    Scenario: Manually adding a lot untracked product into non-receiving location which already has lot tracked product should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        Then the product should have inventory 30 on location "A-1"
        And the product lot report should show added 30, removed 0 and remaining 30 for lot "P1-L1" on location "A-1"
        And I shouldn't be able to manually set 10 of "sku-1" into "A-1" location

    Scenario: Manually adding a lot tracked product into non-receiving location which already has lot tracked product should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        Then the product should have inventory 30 on location "A-1"
        And the product lot report should show added 30, removed 0 and remaining 30 for lot "P1-L1" on location "A-1"
        And I shouldn't be able to manually set 10 of "sku-with-lot-tracking-2" with lot "P2-L1" into "A-1" location
        And the product lot report should show nothing for lot "P2-L1" on location "A-1"

    Scenario: Manually adding lot tracked product to location without providing lot information should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        Then I shouldn't be able to manually set 10 of "sku-with-lot-tracking-2" into "A-1" location

    Scenario: Transferring lot untracked to empty location
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 11 of "sku-1" into "A-1" location
        And I transfer 10 of "sku-1" from "A-1" location into "A-2" location
        Then the product should have inventory 1 on location "A-1"
        And the product should have inventory 10 on location "A-2"

    Scenario: Transferring lot untracked to non-empty location without lot
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 11 of "sku-1" into "A-1" location
        Then the product should have inventory 11 on location "A-1"
        And I manually set 11 of "sku-2" into "A-2" location
        And the product should have inventory 11 on location "A-2"
        And I transfer 10 of "sku-1" from "A-1" location into "A-2" location
        And the product should have inventory 1 on location "A-1"
        And the product should have inventory 10 on location "A-2"

    Scenario: Transferring lot untracked to non-empty location with lot should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 11 of "sku-1" into "A-1" location
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-2" location
        Then I shouldn't be able to transfer 10 of "sku-1" from "A-1" location into "A-2" location

    Scenario: Transferring lot tracked to empty location
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I transfer 10 of "sku-with-lot-tracking-1" from "A-1" location into "A-2" location
        Then the product should have inventory 20 on location "A-1"
        And the product should have inventory 10 on location "A-2"
        And the product lot report should show added 30, removed 10 and remaining 20 for lot "P1-L1" on location "A-1"
        And the product lot report should show added 10, removed 0 and remaining 10 for lot "P1-L1" on location "A-2"

    Scenario: Transferring lot tracked with multiple lots from receiving
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 10 of "sku-with-lot-tracking-1" with lot "P1-L1" into "Receiving" location
        And I manually set 20 of "sku-with-lot-tracking-1" with lot "P1-L2" into "Receiving" location
        And I transfer 10 of "sku-with-lot-tracking-1" with lot "P1-L1" from "Receiving" location into "A-1" location
        Then the product should have inventory 20 on location "Receiving"
        And the product should have inventory 10 on location "A-1"
        And the product lot report should show added 10, removed 10 and remaining 0 for lot "P1-L1" on location "Receiving"
        And the product lot report should show added 20, removed 0 and remaining 20 for lot "P1-L2" on location "Receiving"
        And the product lot report should show added 10, removed 0 and remaining 10 for lot "P1-L1" on location "A-1"

    Scenario: Transferring lot tracked to non-empty location without lot should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I manually set 11 of "sku-1" into "A-2" location
        Then I shouldn't be able to transfer 10 of "sku-with-lot-tracking-1" from "A-1" location into "A-2" location

    Scenario: Transferring lot tracked to non-empty location with different lot should not be possible
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        Then I shouldn't be able to transfer 10 of "sku-with-lot-tracking-1" from "A-1" location into "A-2" location

    Scenario: Transferring lot tracked to non-empty location with same lot
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-2" location
        And I transfer 10 of "sku-with-lot-tracking-1" from "A-1" location into "A-2" location
        Then the product should have inventory 20 on location "A-1"
        And the product should have inventory 40 on location "A-2"
        And the product lot report should show added 30, removed 10 and remaining 20 for lot "P1-L1" on location "A-1"
        And the product lot report should show added 40, removed 0 and remaining 40 for lot "P1-L1" on location "A-2"

    Scenario: Shipping order item with lot information
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" got the order number "O-001" for 10 SKU "sku-with-lot-tracking-1"
        And the order "O-001" is set to be shipped to "90210", "United States"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 5 of "sku-with-lot-tracking-1" from "A-1" location
        And I pack 5 of "sku-with-lot-tracking-1" from "A-2" location
        And I ship the order using "Generic" method
        Then the product should have inventory 25 on location "A-1"
        And the product should have inventory 25 on location "A-2"
        And the product lot report should show added 30, removed 5 and remaining 25 for lot "P1-L1" on location "A-1"
        And the product lot report should show added 30, removed 5 and remaining 25 for lot "P1-L2" on location "A-2"

    Scenario: Receiving lot tracked into location that had lot tracking before but was removed
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" got the order number "O-001" for 30 SKU "sku-with-lot-tracking-1"
        And the order "O-001" is set to be shipped to "90210", "United States"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 30 of "sku-with-lot-tracking-1" from "A-1" location
        And I ship the order using "Generic" method
        Then the product should have inventory 0 on location "A-1"
        And the product lot report should show added 30, removed 30 and remaining 0 for lot "P1-L1" on location "A-1"
        And I manually set 30 of "sku-with-lot-tracking-2" with lot "P2-L1" into "A-2" location
        And I transfer 15 of "sku-with-lot-tracking-2" from "A-2" location into "A-1" location
        And the product lot report should show added 30, removed 15 and remaining 15 for lot "P2-L1" on location "A-2"
        And the product lot report should show added 15, removed 0 and remaining 15 for lot "P2-L1" on location "A-1"

    @picking
    Scenario: Picking - general setting FEFO
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And the customer "Test 3PL" has the setting "lot_priority" set to "FEFO"
        And I will work with customer "Test 3PL Client"
        And the current UTC date is "2023-10-23" and the time is "10:00:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And the current UTC date is "2023-10-23" and the time is "10:01:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        And the current UTC date is "2023-10-23" and the time is "10:02:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L3" into "A-3" location
        And the customer "Test 3PL Client" got the order number "O-001" for 30 SKU "sku-with-lot-tracking-1"
        And I start picking order "O-001"
        Then the picking batch asks me to pick "sku-with-lot-tracking-1" from "A-2" location

    @picking
    Scenario: Picking - general setting FIFO
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And the customer "Test 3PL" has the setting "lot_priority" set to "FIFO"
        And I will work with customer "Test 3PL Client"
        And the current UTC date is "2023-10-23" and the time is "10:00:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And the current UTC date is "2023-10-23" and the time is "10:01:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        And the current UTC date is "2023-10-23" and the time is "10:02:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L3" into "A-3" location
        And the customer "Test 3PL Client" got the order number "O-001" for 30 SKU "sku-with-lot-tracking-1"
        And I start picking order "O-001"
        Then the picking batch asks me to pick "sku-with-lot-tracking-1" from "A-1" location

    @picking
    Scenario: Picking - general setting LIFO
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And the customer "Test 3PL" has the setting "lot_priority" set to "LIFO"
        And I will work with customer "Test 3PL Client"
        And the current UTC date is "2023-10-23" and the time is "10:00:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And the current UTC date is "2023-10-23" and the time is "10:01:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        And the current UTC date is "2023-10-23" and the time is "10:02:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L3" into "A-3" location
        And the customer "Test 3PL Client" got the order number "O-001" for 30 SKU "sku-with-lot-tracking-1"
        And I start picking order "O-001"
        Then the picking batch asks me to pick "sku-with-lot-tracking-1" from "A-3" location

    @picking
    Scenario: Picking - general setting FIFO, product override - FEFO
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And the customer "Test 3PL" has the setting "lot_priority" set to "FIFO"
        And I will work with customer "Test 3PL Client"
        And the current UTC date is "2023-10-23" and the time is "10:00:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And the current UTC date is "2023-10-23" and the time is "10:01:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        And the current UTC date is "2023-10-23" and the time is "10:02:00"
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L3" into "A-3" location
        And the product has lot priority set to "FEFO"
        And the customer "Test 3PL Client" got the order number "O-001" for 30 SKU "sku-with-lot-tracking-1"
        And I start picking order "O-001"
        Then the picking batch asks me to pick "sku-with-lot-tracking-1" from "A-1" location

    @picking @picking_route_strategy
    Scenario: Picking - general lot priority set to FEFO, picking route strategy set to "Alphanumerically"
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And the customer "Test 3PL" has the setting "lot_priority" set to "FEFO"
        And the customer "Test 3PL" has the setting "picking_route_strategy" set to "alphanumerically"
        And I will work with customer "Test 3PL Client"
        And I manually set 60 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I manually set 60 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L4" into "A-3" location
        And the customer "Test 3PL Client" got the order number "O-001" for 30 SKU "sku-with-lot-tracking-1"
        And I start picking order "O-001"
        Then the picking batch asks me to pick "sku-with-lot-tracking-1" from "A-2" location

    @picking @picking_route_strategy
    Scenario: Picking - general lot priority set to FEFO, picking route strategy set to "Most inventory"
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And the customer "Test 3PL" has the setting "lot_priority" set to "FEFO"
        And the customer "Test 3PL" has the setting "picking_route_strategy" set to "most_inventory"
        And I will work with customer "Test 3PL Client"
        And I manually set 60 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I manually set 60 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L4" into "A-3" location
        And the customer "Test 3PL Client" got the order number "O-001" for 30 SKU "sku-with-lot-tracking-1"
        And I start picking order "O-001"
        Then the picking batch asks me to pick "sku-with-lot-tracking-1" from "A-2" location

    @picking @picking_route_strategy
    Scenario: Picking - general lot priority set to FEFO, picking route strategy set to "Least inventory"
        When the user "roger+test_3pl@packiyo.com" is authenticated
        And the customer "Test 3PL" has the setting "lot_priority" set to "FEFO"
        And the customer "Test 3PL" has the setting "picking_route_strategy" set to "least_inventory"
        And I will work with customer "Test 3PL Client"
        And I manually set 60 of "sku-with-lot-tracking-1" with lot "P1-L1" into "A-1" location
        And I manually set 60 of "sku-with-lot-tracking-1" with lot "P1-L2" into "A-2" location
        And I manually set 30 of "sku-with-lot-tracking-1" with lot "P1-L4" into "A-3" location
        And the customer "Test 3PL Client" got the order number "O-001" for 30 SKU "sku-with-lot-tracking-1"
        And I start picking order "O-001"
        Then the picking batch asks me to pick "sku-with-lot-tracking-1" from "A-3" location

