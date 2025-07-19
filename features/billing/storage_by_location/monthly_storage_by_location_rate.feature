@inventory @locations @mongo
Feature: Charging monthly occupied locations on-the-fly for 3PL clients
    As the owner of a 3PL business
    I want my customers to be charged on-the-fly for the locations they occupy with a month billing rate

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse 2" in "United States"
        And the warehouse "Test Warehouse 2" has 10 locations of type "Bin-2"
        And the warehouse "Test Warehouse 2" has 10 locations of type "Shelve-2"
        And the warehouse "Test Warehouse 2" has 10 locations of type "Pallet-2"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a storage by location rate "Test Storage Rate Control" on rate card "Test Rate Card" with fee 5.0
        # Set up another 3PL with a different timezone.
        And a 3PL called "Another 3PL" based in "United States"
        And the customer "Another 3PL" has a warehouse named "Another Warehouse" in "United States"
        And the customer "Another 3PL" has a warehouse named "Another Warehouse" in "United States"
        And the warehouse "Another Warehouse" has 10 locations of type "Bin"
        And a customer called "Another 3PL Client" based in "United States" client of 3PL "Another 3PL"
        And the customer "Another 3PL Client" has an SKU "test-product-gray" named "Test Product Gray" priced at 4.01

    Scenario: Two location types configured, three occupied locations, charges two
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Pallet"
        And the storage by location rate "Test Storage Rate" applies to "Bin-2"
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Pallet-0003" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse 2" had 1 SKU "test-product-red" in location "Bin-2-0002" from "2023-09-01" to "2023-09-30"
        When I calculate the locations occupied by all clients from "2023-08-30" to "2023-10-01"
        Then the client "Test 3PL Client" should have 2 storage by location charges that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59"
        And the client "Test 3PL Client" should have a storage by location charge that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59" for the warehouse "Test Warehouse", billing rate "Test Storage Rate" and location type "Pallet"
        And this charge should have a quantity of 1 and total charge of 2.0
        And this charge's description should be "Monthly charge for the period from 2023-09-01 00:00:00 to 2023-09-30 23:59:59 for occupying location type Pallet in the warehouse Test Warehouse"
        And the client "Test 3PL Client" should have a storage by location charge that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59" for the warehouse "Test Warehouse 2", billing rate "Test Storage Rate" and location type "Bin-2"
        And this charge should have a quantity of 1 and total charge of 2.0
        And this charge's description should be "Monthly charge for the period from 2023-09-01 00:00:00 to 2023-09-30 23:59:59 for occupying location type Bin-2 in the warehouse Test Warehouse 2"
        And the client "Test 3PL Client 2" should have 0 storage by location charges that bills from "2023-09-01 00:00:00" to "2023-09-01 23:59:59"

    Scenario: Applies to all location types, four occupied location types, charges three
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2023-08-30" to "2023-09-01"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2023-08-30" to "2023-09-01"
        And the warehouse "Test Warehouse 2" had 1 SKU "test-product-red" in location "Bin-2-0002" from "2023-09-01" to "2023-09-30"
        And the warehouse "Test Warehouse 2" had 1 SKU "test-product-red" in location "Shelve-2-0002" from "2023-09-01" to "2023-09-30"
        When I calculate the locations occupied by all clients from "2023-08-30" to "2023-10-01"
        Then the client "Test 3PL Client" should have 3 storage by location charges that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59"
        And the client "Test 3PL Client" should have a storage by location charge that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59" for the warehouse "Test Warehouse", billing rate "Test Storage Rate" and location type "Bin"
        And this charge should have a quantity of 2 and total charge of 4.0
        And this charge's description should be "Monthly charge for the period from 2023-09-01 00:00:00 to 2023-09-30 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And the client "Test 3PL Client" should have a storage by location charge that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59" for the warehouse "Test Warehouse 2", billing rate "Test Storage Rate" and location type "Bin-2"
        And this charge should have a quantity of 1 and total charge of 2.0
        And this charge's description should be "Monthly charge for the period from 2023-09-01 00:00:00 to 2023-09-30 23:59:59 for occupying location type Bin-2 in the warehouse Test Warehouse 2"
        And the client "Test 3PL Client" should have a storage by location charge that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59" for the warehouse "Test Warehouse 2", billing rate "Test Storage Rate" and location type "Shelve-2"
        And this charge should have a quantity of 1 and total charge of 2.0
        And this charge's description should be "Monthly charge for the period from 2023-09-01 00:00:00 to 2023-09-30 23:59:59 for occupying location type Shelve-2 in the warehouse Test Warehouse 2"
        And the client "Test 3PL Client 2" should have 0 storage by location charges that bills from "2023-09-01 00:00:00" to "2023-09-01 23:59:59"

    Scenario: Missing necessary warehouse occupied locations data
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2023-08-30" to "2023-09-01"
        And the warehouse "Test Warehouse 2" had 1 SKU "test-product-red" in location "Bin-2-0002" from "2023-09-01" to "2023-09-30"
        When I calculate the locations occupied by all clients from "2023-08-29" to "2023-09-01"
        And I calculate the locations occupied by all clients from "2023-09-03" to "2023-10-01"
        Then the client "Test 3PL Client" should have 0 storage by location charges that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59"

    Scenario: Do not charge for locations that are not occupied
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2023-08-21" to "2023-08-31"
        And the warehouse "Test Warehouse 2" had 1 SKU "test-product-red" in location "Bin-2-0002" from "2023-08-01" to "2023-08-31"
        When I calculate the locations occupied by all clients from "2023-08-21" to "2023-10-01"
        Then the client "Test 3PL Client" should have 0 storage by location charges that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59"
        And the client "Test 3PL Client 2" should have 0 storage by location charges that bills from "2023-08-01 00:00:00" to "2023-09-30 23:59:59"

    Scenario: Calling the calculation twice on the first day of the month should not create duplicate charges
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Pallet"
        And the storage by location rate "Test Storage Rate" applies to "Bin-2"
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Pallet-0003" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2023-09-01" to "2023-09-01"
        And the warehouse "Test Warehouse 2" had 1 SKU "test-product-red" in location "Bin-2-0002" from "2023-09-01" to "2023-09-30"
        When I calculate the locations occupied by all clients from "2023-08-30" to "2023-10-01"
        And I calculate the locations occupied by all clients from "2023-10-01" to "2023-10-01"
        Then the client "Test 3PL Client" should have 2 storage by location charges that bills from "2023-09-01 00:00:00" to "2023-09-30 23:59:59"
