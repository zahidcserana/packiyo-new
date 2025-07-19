@billing @mongo @charges @receiving @purchase
Feature: Charging for purchase orders
    As the owner of a 3PL business
    I want to be able to charge my customer for purchase orders on-the-fly
    So that I can charge my clients by purchase order automatically and immediately.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And the customer "Test 3PL Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 49.99
        And the customer "Test 3PL Client" has an SKU "test-product-green" named "Test Product Green" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-black" named "Test Product Black" priced at 8.99
        And the customer "Test 3PL Client" has a supplier "Supplier 1"

    Scenario: Charge Purchase Order by items quantity when it is closed with active billing rate
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-red"
        And the purchase order requires "120" of the SKU "test-product-green"
        And the purchase order requires "80" of the SKU "test-product-black"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-red" into "Receiving" location
        And I receive 118 of "test-product-green" into "Receiving" location
        And I receive 80 of "test-product-black" into "Receiving" location
        When the purchase order "Test Purchase Order" is closed
        Then 1 purchase order cache document for order number "Test Purchase Order" was generated
        And the purchase order cache document for order number "Test Purchase Order" with 3 items is generated
        And purchase order cache document contains 1 billing rate with 1 as quantity charge

    Scenario: Charge Purchase Order by items quantity when it is closed with multiple active billing rate, one picking rate and one purchase order billing rate,
        should only get one charge
        Given a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-red"
        And the purchase order requires "120" of the SKU "test-product-green"
        And the purchase order requires "80" of the SKU "test-product-black"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-red" into "Receiving" location
        And I receive 118 of "test-product-green" into "Receiving" location
        And I receive 80 of "test-product-black" into "Receiving" location
        When the purchase order "Test Purchase Order" is closed
        Then 1 purchase order cache document for order number "Test Purchase Order" was generated
        And the purchase order cache document for order number "Test Purchase Order" with 3 items is generated
        And purchase order cache document contains 1 billing rate with 1 as quantity charge

    Scenario: Charge Purchase Order by items quantity when it is closed with active billing rate for 2 skus
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-red"
        And the purchase order requires "120" of the SKU "test-product-green"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-red" into "Receiving" location
        And I receive 118 of "test-product-green" into "Receiving" location
        When the purchase order "Test Purchase Order" is closed
        Then 1 purchase order cache document for order number "Test Purchase Order" was generated
        And the purchase order cache document for order number "Test Purchase Order" with 2 items is generated

    Scenario: Charge Purchase Order by items quantity when it is closed with inactive billing rate
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-red"
        And the purchase order requires "120" of the SKU "test-product-green"
        And the purchase order requires "80" of the SKU "test-product-black"
        And a billing rate "Test Purchase Order Rate" on rate card "Test Rate Card" with 0.75 Fee
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And disable a billing rate "Test Purchase Order Rate"
        And I receive 95 of "test-product-red" into "Receiving" location
        And I receive 118 of "test-product-green" into "Receiving" location
        And I receive 80 of "test-product-black" into "Receiving" location
        When the purchase order "Test Purchase Order" is closed
        Then there is no purchase order cache document for order number "Test Purchase Order"

    Scenario: Charge Purchase Order by items quantity when it is closed Without billing rate
        Given the client "Test 3PL Client" has a pending purchase order "Test Purchase Order" from the supplier "Supplier 1" for 100 of the SKU "test-product-red"
        And the purchase order requires "120" of the SKU "test-product-green"
        And the purchase order requires "80" of the SKU "test-product-black"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I receive 95 of "test-product-red" into "Receiving" location
        And I receive 118 of "test-product-green" into "Receiving" location
        And I receive 80 of "test-product-black" into "Receiving" location
        When the purchase order "Test Purchase Order" is closed
        Then there is no purchase order cache document for order number "Test Purchase Order"
