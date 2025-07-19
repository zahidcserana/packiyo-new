@3pl @billing @invoice @mongo @cache-document @summary
Feature: Generate invoice summary using cache documents on the fly
    As the owner of a 3PL business
    I want to see a breakdown of my invoice by different criteria
    So that I can understand where the cost are going

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
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box' with cost "10.00"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99 with weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99 with weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49 with weighing 5.99
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location
        And I manually set 100 of "test-product-yellow" into "A-1" location

    Scenario: Shipment operation generates invoice, with multiple billing rate is updated during process, invoice line items are generated
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" has a flat fee of "3.50"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the picking rate "Test Picking Rate" has a flat fee of "0.5"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-red"
        And the customer "Test 3PL Client" got the order number "O-003" for 1 SKU "test-product-yellow"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-05-02" with tracking "TN-O-001" and cost "1.00"
        And the 3PL "Test 3PL" ships order "O-002" for client "Test 3PL Client" through "FedEx" on the "2024-05-02" with tracking "TN-O-002" and cost "1.00"
        And the 3PL "Test 3PL" ships order "O-003" for client "Test 3PL Client" through "FedEx" on the "2024-05-02" with tracking "TN-O-003" and cost "1.00"
        When the shipment for order "O-001" cost is "2.00"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 12 invoice items
        And the invoice generation success is executed in the background
        And the invoice summary generation is executed in the background
        And the invoice status is "done"
