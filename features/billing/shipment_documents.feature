@3pl @billing @picking @mongo @cache-document
Feature: Billing for product picking on shipments
    As the owner of a 3PL business
    I want to be able to generate shipment documents on the fly
    such as i can make picking charge calculations efficently

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a location type called "Test Location Type"
        And the location "A-1" is of type "Test Location Type"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99 with weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99 with weighing 5.99
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location

    Scenario: Shipment document is generated when order is shipped
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 shipment document for order number "O-001" is generated

    Scenario: Shipment document is generated when order is shipped for multiple shipments
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-red"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01" with tracking "TN-O-001"
        And the 3PL "Test 3PL" ships order "O-002" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01" with tracking "TN-O-002"
        And any created charges share the timestamp of the corresponding shipment
        Then 1 shipment document for order number "O-001" is generated
        Then 1 shipment document for order number "O-002" is generated

    Scenario: Shipment document is generated when order is shipped, with a billing rate that will not charge, shipment calculated
        billing rates will stay empty
        Given the picking rate "Test Picking Rate" applies when the order is tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        And any created charges share the timestamp of the corresponding shipment
        Then 1 shipment document for order number "O-001" is generated
        And shipment document calculated billing rates has no charges

    Scenario: Shipment document is generated when order is shipped, with two billing rates and one will not charge, shipment calculated
        billing rates will only have one element charge
        Given the picking rate "Test Picking Rate" applies when the order is tagged as "B2B"
        And a picking rate "Test Picking Rate 2" on rate card "Test Rate Card"
        Given the picking rate "Test Picking Rate" applies when the order is not tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        And any created charges share the timestamp of the corresponding shipment
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-04-10" to "2024-04-30"
        And the invoice is calculated in the background
        Then 1 shipment document for order number "O-001" is generated
        And shipment document contains 2 billing rate with 1 as quantity charge
