@3pl @billing @invoice @receiving @storage @cache-document @mongo
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
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the customer "Test 3PL Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test 3PL Client" has a supplier "Supplier 1"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"

    Scenario: Shipment and Receiving operations generates invoice, invoice line items are generated
        Given a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-blue"
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And the 3PL "Test 3PL" ships order "O-002" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-002"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 2 invoice items
        And 1 invoice item for 219.75 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And 1 invoice item for 1.50 has a quantity of 1 and the description "Shipment Number: TN-O-002 | FedEx via Ground, order no. O-002"
        And the invoice generation success is executed in the background
        And the invoice status is "done"

    Scenario: Shipment, Storage and Receiving operations generates invoice, invoice line items are generated
        Given a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 1.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-blue"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0001" from "2024-05-01" to "2024-05-15"
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And the 3PL "Test 3PL" ships order "O-002" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-002"
        And we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "2024-05-01" to "2024-05-15"
        And a storage by location rate "Test Storage Rate" was updated at "2024-04-28"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 17 invoice items
        And 1 invoice item for 219.75 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And 1 invoice item for 1.50 has a quantity of 1 and the description "Shipment Number: TN-O-002 | FedEx via Ground, order no. O-002"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-01 00:00:00 to 2024-05-01 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-02 00:00:00 to 2024-05-02 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-03 00:00:00 to 2024-05-03 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-04 00:00:00 to 2024-05-04 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-05 00:00:00 to 2024-05-05 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-06 00:00:00 to 2024-05-06 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-07 00:00:00 to 2024-05-07 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-08 00:00:00 to 2024-05-08 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-09 00:00:00 to 2024-05-09 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-10 00:00:00 to 2024-05-10 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-11 00:00:00 to 2024-05-11 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-12 00:00:00 to 2024-05-12 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-13 00:00:00 to 2024-05-13 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And 1 invoice item for 1 has a quantity of 1 and the description "Daily charge for the period from 2024-05-14 00:00:00 to 2024-05-14 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
        And the invoice generation success is executed in the background
        And the invoice status is "done"

    Scenario: When new rate card is assign, invoice generates new invoice items
        Given a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 1.0
        And the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-blue"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0001" from "2024-05-01" to "2024-05-15"
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And the 3PL "Test 3PL" ships order "O-002" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-002"
        And we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "2024-05-01" to "2024-05-15"
        And a storage by location rate "Test Storage Rate" was updated at "2024-04-28"
        And rate card "Test Rate Card" is unassigned to client "Test 3PL Client"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card 2" assigned to its client "Test 3PL Client"
        And a shipping label rate "Test Shipping Label Rate 2" on rate card "Test Rate Card 2"
        And the shipping label rate "Test Shipping Label Rate 2" applies when other rate matches
        And the shipping label rate has a flat fee of "3.0"
        And a storage by location rate "Test Storage Rate 2" on rate card "Test Rate Card 2" with fee 2.0
        And the storage by location rate "Test Storage Rate 2" applies to "Bin"
        And the storage by location rate "Test Storage Rate 2" invoices by "day"
        And a storage by location rate "Test Storage Rate 2" was updated at "2024-04-28"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 16 invoice items
        And 1 invoice item for 3 has a quantity of 1 and the description "Shipment Number: TN-O-002 | FedEx via Ground, order no. O-002"
        And 1 invoice item for 2 has a quantity of 1 and the description "Daily charge for the period from 2024-05-01 00:00:00 to 2024-05-01 23:59:59 for occupying location type Bin in the warehouse Test Warehouse"
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
        And the invoice generation success is executed in the background
        And the invoice status is "done"
