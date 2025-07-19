@3pl @billing @invoice @mongo @cache-document
Feature: Generate invoice using cache documents on the fly
    As the owner of a 3PL business
    I want to be able to generate invoice using cache documents from billable operations
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

    Scenario: Shipment operation generates invoice, invoice line items are generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 1.50 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"
        And the invoice generation success is executed in the background
        And the invoice status is "done"

    Scenario: Shipment operation generates invoice, with no charge documents, invoice line items are generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And we lost the shipping charge cache for the order number "O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 1.50 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"
        And the invoice generation success is executed in the background
        And the invoice status is "done"
        And a new 1 shipping label charged item for 1.5 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001" was generated

    Scenario: Shipment operation generates invoice, shipment documents doesnt contains billing rates or charges, invoice line items are generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And we lost the shipping charge cache for the order number "O-001"
        And shipment document for the order number "O-001" doesnt contain any billing rate
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 1.50 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"
        And the invoice generation success is executed in the background
        And the invoice status is "done"
        And 1 shipment document for order number "O-001" is generated
        And shipment document contains 1 billing rate with 1 as quantity charge

    Scenario: Shipment operation generates invoice, billing rate is updated during process, invoice line items are generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And a shipping label rate "Test Shipping Label Rate" was updated at "2024-04-01"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        When the shipping label rate has a flat fee of "2.00"
        And a shipping label rate "Test Shipping Label Rate" was updated at "2024-05-01"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 2.00 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"
        And the invoice generation success is executed in the background
        And the invoice status is "done"
        And a new 1 shipping label charged item for 2 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001" was generated

    Scenario: Shipment operation generates invoice, with multiple billing rate is updated during process, invoice line items are generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And a shipping label rate "Test Shipping Label Rate" was updated at "2024-04-01"
        And a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" has a flat fee of "3.50"
        And a packaging rate "Test Package Rate" was updated at "2024-04-01"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the picking rate "Test Picking Rate" has a flat fee of "0.5"
        And a picking rate "Test Picking Rate" was updated at "2024-04-01"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        When the shipping label rate "Test Shipping Label Rate" has a flat fee of "2.00"
        And a shipping label rate "Test Shipping Label Rate" was updated at "2024-05-01"
        And the package rate "Test Package Rate" has a flat fee of "0.75"
        And a packaging rate "Test Package Rate" was updated at "2024-05-01"
        And the picking rate "Test Picking Rate" has a flat fee of "3.00"
        And a picking rate "Test Picking Rate" was updated at "2024-05-01"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 4 invoice items
        And 1 invoice item for 2.00 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"
        And 1 invoice item for 3.00 has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for 0 has a quantity of 1 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue first pick fee"
        And 1 invoice item for 0.75 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'
        And the invoice generation success is executed in the background
        And the invoice status is "done"
        And a new 1 shipping label charged item for 2 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001" was generated
        And a new 1 package charged item for 0.75 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box' was generated
        And a new 1 picking charged item for 3.00 has a quantity of 1 and the description "Flat fee for order number O-001" was generated

    Scenario: Shipment operation generates invoice, with multiple billing rate, invoice line items are generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" has a flat fee of "3.50"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the picking rate "Test Picking Rate" has a flat fee of "0.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        When the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 4 invoice items
        And 1 invoice item for 1.50 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"
        And 1 invoice item for 0.50 has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for 0 has a quantity of 1 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue first pick fee"
        And 1 invoice item for 3.50 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'
        And the invoice generation success is executed in the background
        And the invoice status is "done"

    Scenario: Shipment operation generates invoice, with picking billing rate, invoice line items are generated
        Given a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the picking rate "Test Picking Rate" has a flat fee of "0.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        When the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 2 invoice items
        And 1 invoice item for 0.50 has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for 0 has a quantity of 1 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue first pick fee"
        And the invoice generation success is executed in the background
        And the invoice status is "done"

    Scenario: Shipment operation generates invoice, with shipment cache document missing, invoice line items are not generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        When the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And we lost the shipment caches for the order number "O-001"
        And the invoice generation on the fly job is executed in the background but with error during jobs
        Then the invoice generated on the fly should have 0 invoice items
        And the invoice status is "failed"

    Scenario: Shipment operation generates invoice, with invoice cache document missing, invoice line items are not generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        When the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And we lost the invoice cache document
        And the invoice generation on the fly job is executed in the background but with error during jobs
        Then the invoice generated on the fly should have 0 invoice items
        And the invoice status is "failed"

    Scenario: Shipment operation generates invoice, but we lost the charge documents, invoice line items are generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-01" with tracking "TN-O-001"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And we lost the shipping charge cache for the order number "O-001"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 1.50 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"
        And the invoice generation success is executed in the background
        And the invoice status is "done"
