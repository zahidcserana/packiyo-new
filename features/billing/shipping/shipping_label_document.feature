@3pl @billing @shipping @mongo @cache-document
Feature: Billing for shipping over different periods
    As the owner of a 3PL business
    I want to be able to generate shipping label rates charges on the fly
    So that I can charge my customers efficiently.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a shipping carrier "DHL" and a shipping method "Ground"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a location type called "Test Location Type"
        And the location "A-1" is of type "Test Location Type"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99 with weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99 with weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49 with weighing 5.99
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location
        And I manually set 100 of "test-product-yellow" into "A-1" location

    Scenario: Billing a default shipping label rate with shipments within the period will generate cache document on the fly
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 shipping label charges cache for order number "O-001" are generated

    Scenario: For an order tag "B2B", Billing a default shipping label rate apply to orders with tags "B2B"
    with shipments within the period will generate cache document on the fly
        Given the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 shipping label charges cache for order number "O-001" are generated

    Scenario: For an order tag "B2B", Billing a default shipping label rate apply to orders with tags "B2C"
    with shipments within the period will not generate cache document on the fly
        Given the shipping label rate "Test Shipping Label Rate" applies when other rate matches
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2C"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 shipping label charges cache for order number "O-001" are generated

    Scenario: For an shipment with "FEDEX", Billing a default shipping label rate apply to shipments with "DHL" as shipping methods
    with shipments within the period will not generate cache document on the fly
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "DHL" to all shipment methods
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 shipping label charges cache for order number "O-001" are generated

    Scenario: For an shipment with "FedEx", Billing a default shipping label rate apply to shipments with "FedEx" as shipping methods
    with shipments within the period will not generate cache document on the fly
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx" to all shipment methods
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 shipping label charges cache for order number "O-001" are generated

    Scenario: For a 3pl without the wallet flag, billing a default shipping label rate with shipments within the period will not generate
    cache document on the fly
        Given the customer "Test 3PL" has the feature flag "App\Features\Wallet" off
        And the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 shipping label charges cache for order number "O-001" are generated

    Scenario Outline: Billing a default shipping label rate with the shipments within the period
    will generate cache document on the fly with flat fee
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of <flat_fee>
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 shipping label charges cache for order number "O-001" are generated
        And 1 charge item for <flat_fee> has a quantity of 1.00
        Examples:
            | flat_fee |
            | 2        |
            | 3.5      |

    Scenario: Billing a default shipping label rate for an order with multiple shipments within the period will generate
    multiple cache documents on the fly
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of "2"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" requires 1 units of SKU "test-product-red"
        And the order number "O-001" for 3pl client "Test 3PL Client" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 1 | test-product-blue | 001|
            | #2 | 6" x 6" x 6" Brown Box | 1 | test-product-red | 002 |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001" the order shipped event is dispatch
        Then 1 shipment document for order number "O-001" is generated
        And shipment document for order number "O-001" contains "2" shipments
        And 2 shipping label charges cache for order number "O-001" are generated

    Scenario: Billing a default shipping label rate for an order with multiple shipments, where by chance one package is assign to another order by mistake, within the period will generate
    an error
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of "2"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" requires 1 units of SKU "test-product-red"
        And the order number "O-001" for 3pl client "Test 3PL Client" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 1 | test-product-blue | 001|
            | #2 | 6" x 6" x 6" Brown Box | 1 | test-product-red | 002 |
        And the shipment number "002" was assign to order number "O-002" for 3pl "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001" the order shipped event is dispatch
        Then 0 shipment document for order number "O-001" is generated

    Scenario: Billing a default shipping label rate with shipments within the period will generate cache document on the fly
    with specific charged item values
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 shipping label charges cache for order number "O-001" are generated
        And 1 shipping label charged item for 1.5 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"

    Scenario: Billing a default shipping label rate with shipments within the period will generate cache document on the fly. Shipment document is updated
    with specific charged item values
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 shipping label charges cache for order number "O-001" are generated
        And 1 shipping label charged item for 1.5 has a quantity of 1 and the description "Shipment Number: TN-O-001 | FedEx via Ground, order no. O-001"
        And 1 shipment document for order number "O-001" is generated
        And shipment document contains 1 billing rate with 1 as quantity charge

