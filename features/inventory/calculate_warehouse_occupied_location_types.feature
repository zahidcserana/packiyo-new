@inventory @locations @mongo
Feature: Calculating the warehouse occupied location types by day for 3PL clients
    As the owner of a 3PL business
    I want to know, on a daily basis, the total amount of occupied locations by type for each of my clients in each of my warehouses

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse 2" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And the warehouse "Test Warehouse 2" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse 2" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse 2" has 10 locations of type "Pallet"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        # Set up another 3PL with a different timezone.
        And a 3PL called "Another 3PL" based in "United States"
        And the customer "Another 3PL" has a warehouse named "Another Warehouse" in "United States"
        And the customer "Another 3PL" has a warehouse named "Another Warehouse" in "United States"
        And the warehouse "Another Warehouse" has 10 locations of type "Bin"
        And a customer called "Another 3PL Client" based in "United States" client of 3PL "Another 3PL"
        And the customer "Another 3PL Client" has an SKU "test-product-gray" named "Test Product Gray" priced at 4.01

    Scenario: Calculating occupied warehouse location types on the date
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 4 SKU "test-product-red" in location "Bin-0001" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 6 SKU "test-product-blue" in location "Bin-0001" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 2 SKU "test-product-red" in location "Bin-0002" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-yellow" in location "Bin-0002" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 4 SKU "test-product-red" in location "Bin-0003" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 2 SKU "test-product-blue" in location "Shelve-0001" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 4 SKU "test-product-red" in location "Shelve-0002" from "2023-09-01" to "2023-09-01"
        And a member user "val+another_3pl@packiyo.com" named "Val" based in "United States"
        And the user "val+another_3pl@packiyo.com" belongs to the customer "Another 3PL"
        And the user "val+another_3pl@packiyo.com" has the setting "timezone" set to "America/Los_Angeles"
        And the warehouse "Another Warehouse" had 4 SKU "test-product-gray" in location "Bin-0001" from "2023-09-01" to "2023-09-01"
        And the current UTC date is "2023-09-02" and the time is "04:01:32"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have a warehouse aggregation for the warehouse "Test Warehouse" on date "2023-09-01" with:
            | type   | location    |
            | Bin    | Bin-0001    |
            | Bin    | Bin-0002    |
            | Bin    | Bin-0003    |
            | Shelve | Shelve-0001 |
            | Shelve | Shelve-0002 |
        And the client "Test 3PL Client" should have a warehouse aggregation for the warehouse "Test Warehouse 2" on date "2023-09-01" with:
            | type   | occupied_amount |
        And the client "Another 3PL Client" should have 0 warehouse occupied location types

    Scenario: Calculating twice on the same date should not generate duplicates
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 4 SKU "test-product-red" in location "Bin-0001" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 6 SKU "test-product-blue" in location "Bin-0001" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 2 SKU "test-product-red" in location "Bin-0002" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-yellow" in location "Bin-0002" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 4 SKU "test-product-red" in location "Bin-0003" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 2 SKU "test-product-blue" in location "Shelve-0001" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 4 SKU "test-product-red" in location "Shelve-0002" from "2023-09-01" to "2023-09-01"
        And a member user "val+another_3pl@packiyo.com" named "Val" based in "United States"
        And the user "val+another_3pl@packiyo.com" belongs to the customer "Another 3PL"
        And the user "val+another_3pl@packiyo.com" has the setting "timezone" set to "America/Los_Angeles"
        And the warehouse "Another Warehouse" had 4 SKU "test-product-gray" in location "Bin-0001" from "2023-09-01" to "2023-09-01"
        And the current UTC date is "2023-09-02" and the time is "04:01:32"
        When I calculate the locations occupied by all clients
        And I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have 2 warehouse occupied location types
        And the client "Another 3PL Client" should have 0 warehouse occupied location types
