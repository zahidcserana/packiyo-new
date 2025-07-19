@3pl @billing @invoice @mongo
Feature: Generate invoice using cache documents on the fly
    As the owner of a 3PL business
    I want to be able to generate invoice using cache documents from receiving billable operations
    If missing charge document generate document

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a location type called "Test Location Type"
        And the location "A-1" is of type "Test Location Type"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99 with weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99 with weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49 with weighing 5.99
        And the customer "Test 3PL Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test 3PL Client" has a supplier "Supplier 1"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location
        And I manually set 100 of "test-product-yellow" into "A-1" location

    Scenario: Storage operation generates invoice, invoice line items are generated
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-05-15"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-01" to "2024-05-15"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "2024-05-01" to "2024-05-15"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And a storage by location rate "Test Storage Rate" was updated at "2024-04-28"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        And the invoice generation success is executed in the background
        Then the invoice status is "done"
        And the invoice generated on the fly should have 15 invoice items
        And 1 invoice item for 4 has a quantity of 2 and the description "Daily charge for the period from 2024-05-01 00:00:00 to 2024-05-01 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-02 00:00:00 to 2024-05-02 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-03 00:00:00 to 2024-05-03 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-04 00:00:00 to 2024-05-04 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-05 00:00:00 to 2024-05-05 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-06 00:00:00 to 2024-05-06 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-07 00:00:00 to 2024-05-07 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-08 00:00:00 to 2024-05-08 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-09 00:00:00 to 2024-05-09 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-10 00:00:00 to 2024-05-10 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-11 00:00:00 to 2024-05-11 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-12 00:00:00 to 2024-05-12 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-13 00:00:00 to 2024-05-13 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-14 00:00:00 to 2024-05-14 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"

    Scenario: Storage operation generates invoice, billing rate is updated and recalculation is executed, invoice line items are generated
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-16" to "2024-05-31"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-16" to "2024-05-31"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And a storage by location rate "Test Storage Rate" was updated at "2024-04-28"
        When we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "2024-05-16" to "2024-05-31"
        And the storage by location rate "Test Storage Rate" applies with fee 1.0
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-16" to "2024-05-31"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-16" and "2024-05-31"
        And the invoice generation on the fly job is executed in the background
        And the invoice generation success is executed in the background
        Then the invoice status is "done"
        And the invoice generated on the fly should have 16 invoice items

    Scenario: Storage operation generates invoice, some charge documents are missing so recalculation is executed, invoice line items are generated
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-16" to "2024-05-31"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-16" to "2024-05-31"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "2024-05-16" to "2024-05-31"
        And a storage by location rate "Test Storage Rate" was updated at "2024-04-28"
        And we lost at most 2 storage by location cache documents
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-16" to "2024-05-31"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-16" and "2024-05-31"
        And the invoice generation on the fly job is executed in the background
        And the invoice generation success is executed in the background
        Then the invoice status is "done"
        And the invoice generated on the fly should have 16 invoice items

    Scenario: Storage operation generates invoice, some charge documents are missing so recalculation is executed, invoice line items are generated
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-16" to "2024-05-31"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-16" to "2024-05-31"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "2024-05-16" to "2024-05-31"
        And a storage by location rate "Test Storage Rate" was updated at "2024-04-28"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-16" to "2024-05-31"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-16" and "2024-05-31"
        And we lost at most 2 warehouse by location cache documents
        And the invoice generation on the fly job is executed in the background but with error during jobs
        Then the invoice status is "failed"

    Scenario: Storage operation generates invoice, with multiple billing rates, invoice line items are generated
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And a storage by location rate "Test Storage Rate 2" on rate card "Test Rate Card" with fee 1.0
        And the storage by location rate "Test Storage Rate 2" applies to "Bin"
        And the storage by location rate "Test Storage Rate 2" invoices by "week"
        And a storage by location rate "Test Storage Rate 3" on rate card "Test Rate Card" with fee 0.25
        And the storage by location rate "Test Storage Rate 3" applies to "Bin"
        And the storage by location rate "Test Storage Rate 3" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-06-01"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "2024-05-01" to "2024-06-02"
        And a storage by location rate "Test Storage Rate" was updated at "2024-04-28"
        And a storage by location rate "Test Storage Rate 2" was updated at "2024-04-28"
        And a storage by location rate "Test Storage Rate 3" was updated at "2024-04-28"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-06-02"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-06-02"
        And the invoice generation on the fly job is executed in the background
        And the invoice generation success is executed in the background
        Then the invoice status is "done"
        And the invoice generated on the fly should have 37 invoice items

    Scenario: Storage operation generates invoice, with multiple billing rates are updated, invoice line items are generated
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And a storage by location rate "Test Storage Rate 2" on rate card "Test Rate Card" with fee 1.0
        And the storage by location rate "Test Storage Rate 2" applies to "Bin"
        And the storage by location rate "Test Storage Rate 2" invoices by "week"
        And a storage by location rate "Test Storage Rate 3" on rate card "Test Rate Card" with fee 0.25
        And the storage by location rate "Test Storage Rate 3" applies to "Bin"
        And the storage by location rate "Test Storage Rate 3" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-06-01"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And a storage by location rate "Test Storage Rate" was updated at "2024-04-28"
        And a storage by location rate "Test Storage Rate 2" was updated at "2024-04-28"
        And a storage by location rate "Test Storage Rate 3" was updated at "2024-04-28"
        When we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "2024-05-01" to "2024-06-02"
        And a storage by location rate "Test Storage Rate 2" was updated
        And a storage by location rate "Test Storage Rate 3" was updated
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-06-02"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-06-02"
        And the invoice generation on the fly job is executed in the background
        And the invoice generation success is executed in the background
        Then the invoice status is "done"
        And the invoice generated on the fly should have 37 invoice items
        And 5 storage cache where deleted during process
