@3pl @billing @package @mongo
Feature: Billing for packages over different periods
    As the owner of a 3PL business
    I want to be able to generate package rates charges on the fly
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

    Scenario: Billing a package rate with flat fee for shipments within the period will generate cache document on the fly
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with percentage for shipments within the period will generate cache document on the fly
        Given the package rate "Test Package Rate" charges 10% of the base cost of the shipping box
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box' with cost "10.00"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with percentage for shipments within the period will generate cache document on the fly
        Given the package rate "Test Package Rate" charges 10% of the base cost of the shipping box
        And the package rate "Test Package Rate" has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box' with cost "10.00"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 2.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with percentage for shipments within the period will generate cache document on the fly
        and package cost is zero
        Given the package rate "Test Package Rate" charges 10% of the base cost of the shipping box
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box' with cost "0.00"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 0.0 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with flat fee and applies to customer shipping boxes, for shipments within the period will generate cache document on the fly
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with flat fee and applies to customer shipping boxes, for shipments within the
    period will not generate cache document on the fly
        Given the customer "Test 3PL Client" has a shipping box named '6" x 5" x 6" Brown Box'
        And the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl Client" shipping box
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 package charges cache for order number "O-001" are generated

    Scenario: Billing a package rate with flat fee and applies to customer shipping boxes, for shipments within the
    period will generate cache document on the fly
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with flat fee and applies to multiple customer shipping boxes, for shipments within the
    period will generate cache document on the fly
        Given the customer "Test 3PL Client" has a shipping box named '6" x 5" x 6" Brown Box'
        And the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies also when use any of the customer "Test 3pl Client" shipping box
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with flat fee and applies to customer shipping boxes for a specif box, for shipments within the
    period will generate cache document on the fly
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use ship box '6" x 6" x 6" Brown Box' of the customer "Test 3pl"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with flat fee and applies to customer shipping boxes for a specific box, for shipments within the
    period will not generate cache document on the fly
        Given the customer "Test 3PL Client" has a shipping box named '6" x 5" x 6" Brown Box'
        And the customer "Test 3PL Client" has a shipping box named '6" x 5" x 5" Brown Box'
        And the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use ship box '6" x 5" x 6" Brown Box' of the customer "Test 3pl Client"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 package charges cache for order number "O-001" are generated

    Scenario: Billing a package rate with flat fee, applies to customer shipping boxes and the order has the tags in scope, for shipments within the period
    will generate cache document on the fly
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with flat fee, applies to customer shipping boxes and the order does not has the tags in scope, for shipments within the period
    will generate cache document on the fly
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'

    Scenario: Billing a package rate with flat fee, applies to customer shipping boxes and the order does not has the tags in scope, for shipments within the period
    will generate not cache document on the fly
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 0 package charges cache for order number "O-001" are generated

    Scenario: Billing a package rate with flat fee, and applies to customer shipping boxes, for multiple shipments within the period
    will generate cache document on the fly
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" requires 1 units of SKU "test-product-red"
        And the order number "O-001" for 3pl client "Test 3PL Client" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 1 | test-product-blue | 001|
            | #2 | 6" x 6" x 6" Brown Box | 1 | test-product-red | 002 |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001" the order shipped event is dispatch
        Then 2 package charges cache for order number "O-001" are generated

    Scenario: Billing a package rate with flat fee, applies to customer shipping boxes and the order does not has the tags in scope, for shipments within the period
    will generate cache document on the fly. Shipment cache document contains 1 billing rate with charge
        Given the package rate "Test Package Rate" has a flat fee of "1.50"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 package charges cache for order number "O-001" are generated
        And 1 package charged item for 1.5 has a quantity of 1 and the description 'Charge for box name: 6" x 6" x 6" Brown Box'
        And 1 shipment document for order number "O-001" is generated
        And shipment document contains 1 billing rate with 1 as quantity charge
