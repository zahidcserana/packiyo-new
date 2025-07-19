@3pl @billing @invoice @mongo @cache-document
Feature: Making calculation of cache documents with billable operations.
    As the owner of a 3PL business
    I want to be able to calculate if cache documents and billable operations have the same count
    such as i can send Invoice generation event dispatch

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
        And the customer "Test 3PL Client" has a supplier "Supplier 1"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location
        And I manually set 100 of "test-product-yellow" into "A-1" location

    Scenario: When invoice is generated on the fly, for only shipments operation, no error occurs
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        Then the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: When invoice is generated on the fly, for only purchase order operation, no error occurs
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-red"
        And the purchase order requires "120" of the SKU "test-product-blue"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-red" into "Receiving" location
        And I receive 118 of "test-product-blue" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        And the purchase order "Test Purchase Order" was close on "2024-05-10"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        Then 1 purchase order cache document for order number "Test Purchase Order" was generated
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    #Scenario: When invoice is generated on the fly, for only storage operation, no error occurs
    #Scenario: When invoice is generated on the fly, for all billable operation, no error occurs
    #Scenario: When invoice is generated on the fly, for all billable operation, an error occur, we default to legacy process

    Scenario: When invoice is generated on the fly, for only purchase order operation, an error occur, invoice cache is generated
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-red"
        And the purchase order requires "120" of the SKU "test-product-blue"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-red" into "Receiving" location
        And I receive 118 of "test-product-blue" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        And the purchase order "Test Purchase Order" was close on "2024-05-10"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And we lost the purchase caches for the order number "Test Purchase Order"
        Then the invoice is exported in the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: When invoice is generated on the fly, for only shipments operation, an error occur, invoice cache is generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And we lost the shipment caches for the order number "O-001"
        Then the invoice is exported in the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: When invoice is generated on the fly, for only storage operation, an error occurs, invoice cache is generated
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-05-15"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-01" to "2024-05-15"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate the locations occupied by all clients from "2024-05-01" to "2024-05-15"
        And we lost 3 warehouse cache documents for the warehouse "Test Warehouse"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with no additional jobs
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
