@inventory @locations @skip-ci
Feature: Calculating the occupied locations for 3PL clients
    As the owner of a 3PL business
    I want to be able to tell which locations were occupied by specific clients
    So that I can charge my customers for those locations.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
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

    Scenario: Calculating occupied locations with an inventory log on the date
        Given the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2023-09-01" to "2023-09-01"
        When I calculate the locations occupied by the client "Test 3PL Client" on the date "2023-09-02"
        Then the client "Test 3PL Client" should have occupied 1 locations on the date "2023-09-01"

    Scenario: Calculating occupied locations with an occupied location log in the past
        Given the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-09-01" to "2023-09-03"
        When I calculate the locations occupied by the client "Test 3PL Client" on the date "2023-09-02"
        And I calculate the locations occupied by the client "Test 3PL Client" on the date "2023-09-03"
        Then the client "Test 3PL Client" should have occupied 1 locations on the date "2023-09-01"
        And the client "Test 3PL Client" should have occupied 1 locations on the date "2023-09-02"

    Scenario: Calculating occupied locations with an inventory log in the past
        Given the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-09-01" to "2023-09-03"
        When I calculate the locations occupied by the client "Test 3PL Client" on the date "2023-09-03"
        Then the client "Test 3PL Client" should have occupied 1 locations on the date "2023-09-02"

    Scenario: Calculating occupied locations with multiple occupied locations on the dates
        Given the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-09-01" to "2023-09-05"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-09-03" to "2023-09-05"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2023-09-03" to "2023-09-03"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2023-09-05" to "2023-09-05"
        When I calculate the locations occupied by the client "Test 3PL Client" on the date "2023-09-03"
        And I calculate the locations occupied by the client "Test 3PL Client" on the date "2023-09-04"
        And I calculate the locations occupied by the client "Test 3PL Client" on the date "2023-09-05"
        And I calculate the locations occupied by the client "Test 3PL Client" on the date "2023-09-06"
        Then the client "Test 3PL Client" should have occupied 1 locations on the date "2023-09-02"
        And the client "Test 3PL Client" should have occupied 2 locations on the date "2023-09-03"
        And the client "Test 3PL Client" should have occupied 1 locations on the date "2023-09-04"
        And the client "Test 3PL Client" should have occupied 2 locations on the date "2023-09-05"

    Scenario: Calculate occupied locations for one customer, one product, and one bin based on time zone at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0004" from "2022-11-27" to "2022-11-27"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 1 locations on the date "2022-11-27"

    Scenario: Calculate occupied locations for one customer, one product, and two bins based on time zone at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2022-11-27" to "2022-11-27"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0002" from "2022-11-27" to "2022-11-27"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 2 locations on the date "2022-11-27"

    Scenario: Calculate occupied locations for one customer, two products, and one bin based on time zone at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2022-11-27" to "2022-11-27"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0001" from "2022-11-27" to "2022-11-27"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 1 locations on the date "2022-11-27"

    Scenario: Calculate occupied locations for one customer, two products, and two bins based on time zone at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2022-11-27" to "2022-11-27"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2022-11-27" to "2022-11-27"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 2 locations on the date "2022-11-27"

    Scenario: Calculate vacant locations based on existing inventory logs in the past at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0002" from "2022-11-01" to "2022-11-26"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 0 locations on the date "2022-11-27"

    Scenario: Calculate vacant locations based on existing inventory logs in the future at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2022-11-28" to "2022-11-30"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 0 locations on the date "2022-11-27"

    Scenario: Calculate occupied locations for two products based on existing inventory logs in the past at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2022-11-01" to "2022-11-30"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0001" from "2022-11-01" to "2022-11-30"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 1 locations on the date "2022-11-27"

    Scenario: Calculate occupied locations for two locations based on existing inventory logs in the past at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2022-11-01" to "2022-11-26"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0002" from "2022-11-01" to "2022-11-30"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0003" from "2022-11-27" to "2022-11-27"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0004" from "2022-11-28" to "2022-11-30"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 2 locations on the date "2022-11-27"

    Scenario: Calculate occupied locations for a single customer based on time zone at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0001" from "2022-11-01" to "2022-11-30"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2022-11-01" to "2022-11-26"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0003" from "2022-11-28" to "2022-11-30"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0004" from "2022-11-27" to "2022-11-27"
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 2 locations on the date "2022-11-27"

    Scenario: Calculate occupied locations for multiple customers based on time zone at midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2022-11-01" to "2022-11-30"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0002" from "2022-11-01" to "2022-11-26"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2022-11-28" to "2022-11-30"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0004" from "2022-11-27" to "2022-11-27"
        And a member user "val+another_3pl@packiyo.com" named "Val" based in "United States"
        And the user "val+another_3pl@packiyo.com" belongs to the customer "Another 3PL"
        And the user "val+another_3pl@packiyo.com" has the setting "timezone" set to "America/Los_Angeles"
        And the warehouse "Another Warehouse" had 1 SKU "test-product-gray" in location "Bin-0001" from "2022-11-01" to "2022-11-30"
        # Define the time.
        And the current UTC date is "2022-11-28" and the time is "05:00:00"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 2 locations on the date "2022-11-27"

    Scenario: Calculate occupied locations based on time zone, when not exactly midnight
        Given a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2022-11-01" to "2022-11-30"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0002" from "2022-11-01" to "2022-11-26"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2022-11-28" to "2022-11-30"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0004" from "2022-11-27" to "2022-11-27"
        And a member user "val+another_3pl@packiyo.com" named "Val" based in "United States"
        And the user "val+another_3pl@packiyo.com" belongs to the customer "Another 3PL"
        And the user "val+another_3pl@packiyo.com" has the setting "timezone" set to "America/Los_Angeles"
        And the warehouse "Another Warehouse" had 1 SKU "test-product-gray" in location "Bin-0001" from "2022-11-01" to "2022-11-30"
        # Define the time.
        And the current UTC date is "2022-11-28" and the time is "05:01:32"
        When I calculate the locations occupied by all clients
        Then the client "Test 3PL Client" should have occupied 2 locations on the date "2022-11-27"
