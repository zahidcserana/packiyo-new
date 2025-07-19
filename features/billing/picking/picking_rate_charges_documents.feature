@3pl @billing @picking @mongo @cache-document
Feature: Billing for product picking on shipments
    As the owner of a 3PL business
    I want to be able to generate picking caches on the fly

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
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99 with weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99 with weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49 with weighing 5.99
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location
        And I manually set 100 of "test-product-yellow" into "A-1" location

    Scenario: When order is shipped, picking charge cache is generated on the fly with charges for first pick, 1 flat fee and remainder picks
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 5 SKU "test-product-blue"
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I allocate the SKU "test-product-blue"
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 picking billing rate cache for order number "O-001" is generated
        And 1 charged item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 charged item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue first pick fee"
        And 1 charged item for 2 has a quantity of 4.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue remaining picks"

    Scenario: When order is shipped, picking charge cache is generated on the fly with charges for first pick, 1 flat fee and remainder picks
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the picking rate "Test Picking Rate" has a fee of "2" for picks 2 to 6
        And the customer "Test 3PL Client" got the order number "O-001" for 5 SKU "test-product-blue"
        And the order "O-001" requires 5 units of SKU "test-product-red"
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 picking billing rate cache for order number "O-001" is generated
        And 1 charged item for 0 has a quantity of 1 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue first pick fee"
        And 1 charged item for 1 has a quantity of 1.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-red first pick of additional SKU"
        And 1 charged item for 8 has a quantity of 4 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue picks 2 to 6"
        And 1 charged item for 2 has a quantity of 1 and the description "Order: O-001, TN: O-001 | SKU: test-product-red picks 2 to 6"
        And 1 charged item for 1.5 has a quantity of 3.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-red remaining picks"

    Scenario: When order is shipped, picking charge cache is generated on the fly with charges for first pick, 1 flat fee and remainder picks. Shipment document is updated
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the picking rate "Test Picking Rate" has a fee of "2" for picks 2 to 6
        And the customer "Test 3PL Client" got the order number "O-001" for 5 SKU "test-product-blue"
        And the order "O-001" requires 5 units of SKU "test-product-red"
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001"
        Then 1 picking billing rate cache for order number "O-001" is generated
        And 1 charged item for 0 has a quantity of 1 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue first pick fee"
        And 1 charged item for 1 has a quantity of 1.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-red first pick of additional SKU"
        And 1 charged item for 8 has a quantity of 4 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue picks 2 to 6"
        And 1 charged item for 2 has a quantity of 1 and the description "Order: O-001, TN: O-001 | SKU: test-product-red picks 2 to 6"
        And 1 charged item for 1.5 has a quantity of 3.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-red remaining picks"
        And 1 shipment document for order number "O-001" is generated
        And shipment document contains 1 billing rate with 5 as quantity charge
