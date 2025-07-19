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

    Scenario: Receiving operation generates invoice, invoice line items are generated
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 219.75 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And the invoice generation success is executed in the background
        And the invoice status is "done"

    Scenario: Receiving operation generates invoice, but rate card is updated, invoice line items are generated
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And rate card "Test Rate Card" is unassigned to client "Test 3PL Client"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card 2" assigned to its client "Test 3PL Client"
        And a billing rate "Test Purchase Order Rate 2" on rate card "Test Rate Card 2" with 1.5 Fee
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 439.5 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And the invoice generation success is executed in the background
        And the invoice status is "done"

    Scenario: Receiving operation generates invoice, and the billing rate is updated during process, invoice line items are generated
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And billing rate "Test Purchase Order Rate" has Fee 0.75
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 219.75 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And the invoice generation success is executed in the background
        And the invoice status is "done"

    Scenario: Receiving operation generates invoice, and the billing rate is updated with different fee value during process, invoice line items are generated
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And a purchase order rate "Test Purchase Order Rate" was updated at "2024-04-01"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And billing rate "Test Purchase Order Rate" has Fee 1.00
        And a purchase order rate "Test Purchase Order Rate" was updated at "2024-05-01"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background
        Then the invoice generated on the fly should have 1 invoice items
        And 1 invoice item for 293 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And the invoice generation success is executed in the background
        And the invoice status is "done"

        ##TODO missing order tag feature to make these scenario work
    Scenario: Receiving operation generates invoice, and the billing rate is deleted during process, invoice line items are not generated


    Scenario: Receiving operation generates invoice, during process we lost purchase order cache, invoice line items are not generated
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And we lost the purchase caches for the order number "Test Purchase Order"
        And the invoice generation on the fly job is executed in the background but with error during jobs
        Then the invoice generated on the fly should have 0 invoice items
        And the invoice status is "failed"

    Scenario: Receiving operation generates invoice, during process we lost purchase order charge cache, invoice line items are generated
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And we lost the purchase charge caches for the order number "Test Purchase Order"
        And the invoice generation on the fly job is executed in the background but with error during jobs
        Then the invoice generated on the fly should have 1 invoice items
        And the invoice generation success is executed in the background
        And 1 invoice item for 219.75 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And the invoice status is "done"
        And a new 1 purchase order charged item for 219.75 has a quantity of 293.0 and the description "Purchase Order Receiving: Test Purchase Order" was generated

    Scenario: Receiving operation generates invoice, purchase order documents doesnt contains billing rates or charges,
    invoice line items are generated and new calculated billing rate is set in purchase order cache
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And purchase order document for the order number "Test Purchase Order" doesnt contain any billing rate
        And we lost the purchase charge caches for the order number "Test Purchase Order"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background but with error during jobs
        Then the invoice generated on the fly should have 1 invoice items
        And the invoice generation success is executed in the background
        And 1 invoice item for 219.75 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And the invoice status is "done"
        And purchase order cache document contains 1 billing rate with 1 as quantity charge

    Scenario: Receiving operation generates invoice, during process we lost purchase order charge cache, invoice line items are generated
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-blue"
        And the purchase order requires "120" of the SKU "test-product-red"
        And the purchase order requires "80" of the SKU "test-product-yellow"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-blue" into "Receiving" location
        And I receive 118 of "test-product-red" into "Receiving" location
        And I receive 80 of "test-product-yellow" into "Receiving" location
        When the purchase order "Test Purchase Order" was close on "2024-05-10"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-05-01" to "2024-05-15"
        And we lost the purchase charge caches for the order number "Test Purchase Order"
        And the invoice is calculated in the background with additional jobs on the background
        And 1 invoice cache documents are generated between "2024-05-01" and "2024-05-15"
        And the invoice generation on the fly job is executed in the background but with error during jobs
        Then the invoice generated on the fly should have 1 invoice items
        And the invoice generation success is executed in the background
        And 1 invoice item for 219.75 has a quantity of 293 and the description "Purchase Order Receiving: Test Purchase Order"
        And the invoice status is "done"
        And purchase order cache document contains 1 billing rate with 1 as quantity charge
