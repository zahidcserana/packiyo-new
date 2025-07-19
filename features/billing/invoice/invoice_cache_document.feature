@3pl @billing @invoice @mongo @cache-document
Feature: Generating invoice cache documents to make invoice on the fly
    As the owner of a 3PL business
    I want to be able to generate invoice cache documents on the fly
    such as i can generate invoices efficiently

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a shipping carrier "DHL" and a shipping method "Air"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a location type called "Test Location Type"
        And the location "A-1" is of type "Test Location Type"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99 with weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99 with weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49 with weighing 5.99
        And the customer "Test 3PL Client" has a supplier "Supplier 1"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location
        And I manually set 100 of "test-product-yellow" into "A-1" location

    Scenario: 3pl doesnt have mongo feature enable but feature flag is enable, invoice cache document is not generated
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-05-01"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the Mongo db service is not available
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 0 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: 3pl doesnt have feature flag enable, invoice cache document is not generated
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 0 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: 3pl doesnt have feature flag enable, but we are missing the billable operation documents, invoice cache document is generated,
    and billable operation and charges document were recalculated.
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-05-01"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And we lost the shipment caches for the order number "O-001"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: 3pl doesnt have feature flag enable, but we are missing the billable operation documents but we have cache documents, invoice cache document is generated,
    and billable operation and charges document were recalculated.
        Given the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-10" with tracking "TN-O-001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And we lost the shipment caches for the order number "O-001"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And 1 shipment cache document was deleted
        And 1 shipment charge cache document was deleted

    Scenario: 3pl doesnt have feature flag enable, but we are missing the billable operation document but we have cache documents for different billing rates,
    invoice cache document is generated, and billable operation and charges document were recalculated.
        Given the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" has a flat fee of "1.50"
        And the shipping label rate has a flat fee of "1.50"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for the first pick of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-10" with tracking "TN-O-001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And we lost the shipment caches for the order number "O-001"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And 1 shipment cache document was deleted
        And 3 shipment charge cache document was deleted

    Scenario: 3pl does have feature flag enable, invoice cache document is generated
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then the invoice should have 0 invoice items
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: 3pl does have feature flag enable, invoice cache document is generated with one billing rate
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice cache contains 1 billing rate

    Scenario: 3pl does have feature flag enable, with no billing rates assign to rate card, invoice cache document is not generated
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background
        Then 0 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: 3pl does have feature flag enable, invoice cache document is generated with multiple billing rate
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" has a flat fee of "1.50"
        And the shipping label rate has a flat fee of "1.50"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for the first pick of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice cache contains 3 billing rate

    Scenario: 3pl does have feature flag enable, invoice cache document is generated with purchase billing rate
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-blue"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 100 of "test-product-blue" into "Receiving" location
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And  the purchase order "Test Purchase Order" is closed
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice cache contains 1 billing rate

    Scenario: 3pl does have feature flag enable, with receiving and fulfillment operation. invoice cache document is generated with multiple billing rate
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-blue"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 100 of "test-product-blue" into "Receiving" location
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And  the purchase order "Test Purchase Order" is closed
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice cache contains 2 billing rate

    Scenario: 3pl does have feature flag enable, but we are missing the receiving billable operation documents but we have cache documents, invoice cache document is generated
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-blue"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 100 of "test-product-blue" into "Receiving" location
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And the purchase order "Test Purchase Order" was close on "2024-05-10"
        And we lost the purchase Order caches for the order number "Test Purchase Order"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with no additional jobs
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And 1 receiving cache document was deleted
        And 1 receiving charge cache document was deleted

    Scenario: 3pl does have feature flag enable, invoice cache document is generated with storage billing rate
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-05-15"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-01" to "2024-05-15"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate the locations occupied by all clients from "2024-05-01" to "2024-05-15"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice cache contains 1 billing rate

    Scenario: 3pl does have feature flag enable, invoice cache document is generated with storage multiple billing rate
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And a storage by location rate "Test Storage Rate 2" on rate card "Test Rate Card" with fee 1.0
        And the storage by location rate "Test Storage Rate 2" applies to "Bin"
        And the storage by location rate "Test Storage Rate 2" invoices by "week"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-05-15"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-01" to "2024-05-15"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate the locations occupied by all clients from "2024-05-01" to "2024-05-15"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice cache contains 2 billing rate

    Scenario: 3pl does have feature flag enable, but we lost some storage document, invoice cache document is generated
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And a storage by location rate "Test Storage Rate 2" on rate card "Test Rate Card" with fee 1.0
        And the storage by location rate "Test Storage Rate 2" applies to "Bin"
        And the storage by location rate "Test Storage Rate 2" invoices by "week"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-05-15"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-01" to "2024-05-15"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate the locations occupied by all clients from "2024-05-01" to "2024-05-15"
        And we lost 3 warehouse cache documents for the warehouse "Test Warehouse"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with no additional jobs
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: 3pl does have feature flag enable, but we lost all storage document, invoice cache document is generated
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And a storage by location rate "Test Storage Rate 2" on rate card "Test Rate Card" with fee 1.0
        And the storage by location rate "Test Storage Rate 2" applies to "Bin"
        And the storage by location rate "Test Storage Rate 2" invoices by "week"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-05-15"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-01" to "2024-05-15"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate the locations occupied by all clients from "2024-05-01" to "2024-05-15"
        And we lost all the warehouse cache documents for the warehouse "Test Warehouse"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with no additional jobs
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"

    Scenario: 3pl does have feature flag enable, invoice cache document is generated with storage billing rate
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-05-15"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-01" to "2024-05-15"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate the locations occupied by all clients from "2024-05-01" to "2024-05-15"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice cache contains 1 billing rate

    Scenario: 3pl does have feature flag enable, invoice cache document is generated with storage multiple billing rate
        Given no billing card is assign the customer on rate card "Test Rate Card"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 2.0
        And the storage by location rate "Test Storage Rate" applies to "Bin"
        And the storage by location rate "Test Storage Rate" invoices by "day"
        And a storage by location rate "Test Storage Rate 2" on rate card "Test Rate Card" with fee 1.0
        And the storage by location rate "Test Storage Rate 2" applies to "Bin"
        And the storage by location rate "Test Storage Rate 2" invoices by "week"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0002" from "2024-05-01" to "2024-05-15"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-red" in location "Bin-0003" from "2024-05-01" to "2024-05-15"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        When I calculate the locations occupied by all clients from "2024-05-01" to "2024-05-15"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        Then 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice cache contains 2 billing rate
