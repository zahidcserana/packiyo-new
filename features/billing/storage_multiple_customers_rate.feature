@3pl @billing @storage
Feature: Billing for storage by location over different periods
    As the owner of a 3PL business with multiple clients
    I want to be able to charge storage rates by any location
    So that I can charge my customers for the locations they occupied.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 generic locations
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client 2" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client 2" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the 3PL "Test 3PL" has a rate card "Test Rate Card 2" assigned to its client "Test 3PL Client 2"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"

    Scenario: Billing a default storage by location rate with one occupied locations within the period
        Given the storage by location rate "Test Storage Rate" applies to "Bin" location type
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-05-01" to "2023-05-31"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items

    Scenario: Billing a default storage by location rate with one generic location occupied within the period
        Given the storage by location rate "Test Storage Rate" applies to generic locations
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Generic-0001" from "2023-05-01" to "2023-05-31"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items

    Scenario: Billing a default storage by location rate with one occupied generic location
    and one occupied location within the period
        Given the storage by location rate "Test Storage Rate" applies to generic locations
        And the storage by location rate "Test Storage Rate" applies to "Bin" location type
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Generic-0001" from "2023-05-01" to "2023-05-31"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-05-01" to "2023-05-31"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items

    Scenario: Billing a default storage by location rate with two occupied location within the period
        Given the storage by location rate "Test Storage Rate" applies to "Bin" location type
        And the storage by location rate "Test Storage Rate" applies to "Shelve" location type
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Shelve-0001" from "2023-05-01" to "2023-05-31"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-05-01" to "2023-05-31"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items

    Scenario: Billing a default storage by location rate with one occupied generic location
    and two occupied location within the period
        Given the storage by location rate "Test Storage Rate" applies to generic locations
        And the storage by location rate "Test Storage Rate" applies to "Bin" location type
        And the storage by location rate "Test Storage Rate" applies to "Shelve" location type
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Generic-0001" from "2023-05-01" to "2023-05-31"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Shelve-0001" from "2023-05-01" to "2023-05-31"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-05-01" to "2023-05-31"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items

    Scenario:  Billing a default storage for a second customer by location rate with one occupied locations
    from another customer within the periods, should not contain invoice items
        Given the storage by location rate "Test Storage Rate" applies to "Bin" location type
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-05-01" to "2023-05-31"
        When I calculate an invoice for customer "Test 3PL Client 2" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items
