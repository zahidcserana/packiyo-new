@3pl @billing @package @tags @mongo
Feature: Billing for packages over different periods
    As the owner of a 3PL business
    I want to be able to generate package rates charges on filtering by order tags
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
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99 with weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99 with weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49 with weighing 5.99
        And the customer "Test 3PL Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test 3PL Client" has a supplier "Supplier 1"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location
        And I manually set 100 of "test-product-yellow" into "A-1" location

    Scenario: When filtering billing package rates by order tags with a single shipment within the period, and the order has tags,
    a cache document will be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by order tags with a single shipment within the period, and the order has no tags,
    a cache document will not be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by missing order tags with a single shipment within the period, and the order has no tags,
    a cache document will be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by missing order tags with a single shipment within the period, and the order has the tags in scope,
    a cache document will not be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by order tags with a single shipment within the period, and the order has different tags,
    a cache document will not be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "Test"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by missing order tags with a single shipment within the period, and the order has different tags,
    a cache document will be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "Test"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by multiple order tags with a single shipment within the period, and the order has one tag from the scope,
    a cache document will not be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the package rate "Test Package Rate" applies when the order is also tagged as "Test"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by multiple order tags with a single shipment within the period, and the order has same tags from the scope,
    a cache document will be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the package rate "Test Package Rate" applies when the order is also tagged as "Test"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the order "O-001" of client "Test 3PL Client" is also tagged as "Test"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by multiple missing order tags with a single shipment within the period, and the order has one tag from the scope,
    a cache document will be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the package rate "Test Package Rate" applies when the order is also not tagged as "Test"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated

    Scenario: When filtering billing package rates by multiple missing order tags with a single shipment within the period, and the order has one tag from the scope,
    a cache document will not be generated on the fly.
        Given the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the package rate "Test Package Rate" applies when the order is also not tagged as "Test"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the order "O-001" of client "Test 3PL Client" is also tagged as "Test"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 package charges cache for order number "O-001" are generated
