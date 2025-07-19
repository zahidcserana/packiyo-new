@3pl @billing @picking
Feature: Billing for product picking on shipments
    As the owner of a 3PL business
    I want to be able to charge for picking by order and/or product tags
    So that I can charge my customers according to the labour involved in picking each shipment.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a 3PL called "Test 3PL" was created on "2024-04-10"
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
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the customer "Test 3PL Client" has an SKU "test-kit-purple" named "Test Kit Purple" priced at 17.99
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
        And the SKU "test-product-red" is added as a component to the kit product with quantity of 2
        And the customer "Test 3PL Client" has an SKU "test-kit-green" named "Test Kit Green" priced at 14.49
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
        And the SKU "test-product-yellow" is added as a component to the kit product with quantity of 2
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-1" location
        And I manually set 100 of "test-product-red" into "A-1" location
        And I manually set 100 of "test-product-yellow" into "A-1" location
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"

    Scenario: Billing a default picking rate with no shipments within the period
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-04-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should not have any invoice items

    Scenario: Billing a default picking rate with a single shipment within the period
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 2.05 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate filtering by order tags with a single shipment within the period
        Given the picking rate "Test Picking Rate" applies when the order is tagged as "B2B"
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 2.05 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate filtering by missing order tags with a single shipment within the period
        Given the picking rate "Test Picking Rate" applies when the order is not tagged as "B2B"
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2C"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 2.05 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate filtering by product tags with a single shipment within the period
        Given the picking rate "Test Picking Rate" applies when the product is tagged as "heavy"
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the SKU "test-product-blue" of client "Test 3PL Client" is tagged as "heavy"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 2.05 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate filtering by missing product tags with a single shipment within the period
        Given the picking rate "Test Picking Rate" applies when the product is not tagged as "dangerous"
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the SKU "test-product-red" of client "Test 3PL Client" is tagged as "dangerous"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 2.05 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing several picking rates by order and product tags
        Given picking rate "Test Picking Rate" is removed
        And a picking rate "Picking By Order Tag" on rate card "Test Rate Card"
        And the picking rate "Picking By Order Tag" applies when the order is tagged as "B2C"
        And the picking rate "Picking By Order Tag" has a flat fee of "2.05"
        And the picking rate "Picking By Order Tag" has a fee of "1.25" for the first pick of an order
        And the picking rate "Picking By Order Tag" has a fee of "0.50" for the remainder picks of an order
        And a picking rate "Picking By No Order Tag" on rate card "Test Rate Card"
        And the picking rate "Picking By No Order Tag" applies when the product is not tagged as "dangerous"
        And the picking rate "Picking By No Order Tag" has a flat fee of "2.05"
        And the picking rate "Picking By No Order Tag" has a fee of "1.25" for the first pick of an order
        And the picking rate "Picking By No Order Tag" has a fee of "0.50" for the remainder picks of an order
        And the SKU "test-product-blue" of client "Test 3PL Client" is tagged as "safe"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2C"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 6 invoice items
        And 2 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And the picking rate "Picking By Order Tag" billed 4.30 for the "FedEx" shipment on the date "2023-05-01"
        And 2 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick fee"
        And the picking rate "Picking By Order Tag" billed 4.30 for the "FedEx" shipment on the date "2023-05-01"
        And 2 invoice item for 1.00 has a quantity of 2.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Picking By Order Tag" billed 4.30 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing several picking rates by order and product tags
        Given picking rate "Test Picking Rate" is removed
        And a picking rate "Picking By Order Tag" on rate card "Test Rate Card"
        And the picking rate "Picking By Order Tag" applies when the order is tagged as "B2C"
        And the picking rate "Picking By Order Tag" has a flat fee of "2.05"
        And the picking rate "Picking By Order Tag" has a fee of "1.25" for the first pick of an order
        And the picking rate "Picking By Order Tag" has a fee of "0.50" for the remainder picks of an order
        And a picking rate "Picking By No Order Tag" on rate card "Test Rate Card"
        And the picking rate "Picking By No Order Tag" applies when the product is not tagged as "dangerous"
        And the picking rate "Picking By No Order Tag" has a flat fee of "2.05"
        And the picking rate "Picking By No Order Tag" has a fee of "1.25" for the first pick of an order
        And the picking rate "Picking By No Order Tag" has a fee of "0.50" for the remainder picks of an order
        And the SKU "test-product-blue" of client "Test 3PL Client" is tagged as "dangerous"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2C"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And the picking rate "Picking By Order Tag" billed 4.30 for the "FedEx" shipment on the date "2023-05-01"
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick fee"
        And the picking rate "Picking By Order Tag" billed 4.30 for the "FedEx" shipment on the date "2023-05-01"
        And 1 invoice item for 1.00 has a quantity of 2.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Picking By Order Tag" billed 4.30 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that only charges first pick
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for the first pick of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick fee"
        And the picking rate "Test Picking Rate" billed 1.25 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that only charges additional SKU
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.83" for each additional SKU pick
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-blue"
        And the order "O-001" requires 2 units of SKU "test-product-yellow"
        And the order "O-001" requires 2 units of SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 5 invoice items
        And 1 invoice item for 0.83 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-yellow first pick of additional SKU"
        And 1 invoice item for 0.83 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick of additional SKU"
        And the picking rate "Test Picking Rate" billed 1.66 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges by pick range
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.14" for picks 2 to 4
        And the picking rate "Test Picking Rate" has a fee of "0.07" for picks 5 to 6
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-blue"
        And the order "O-001" requires 2 units of SKU "test-product-yellow"
        And the order "O-001" requires 3 units of SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 7 invoice items
        And 1 invoice item for 0.14 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 4"
        And 1 invoice item for 0.14 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-yellow picks 2 to 4"
        And 1 invoice item for 0.14 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 4"
        And 1 invoice item for 0.07 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 5 to 6"
        And the picking rate "Test Picking Rate" billed 0.49 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that only charges for remainder picks
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.33" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 0.66 has a quantity of 2.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed 0.66 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing an order with kits with a picking rate that charges a flat fee and the remainder picks
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "2.00"
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-kit-purple"
        # 2 * test-kit-purple = 4 * test-product-blue + 4 * test-product-red
        And the order "O-001" requires 1 units of SKU "test-kit-green" for real
        # 1 * test-kit-green = 2 * test-product-blue + 2 * test-product-yellow
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 8 invoice items
        And 1 invoice item for 2.00 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And 1 invoice item for 0.00 has a quantity of 1.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue first pick fee"
        And 1 invoice item for 0.00 has a quantity of 1.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 0.00 has a quantity of 1.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-yellow first pick fee"
        And 1 invoice item for 1.50 has a quantity of 3.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue remaining picks"
        And 1 invoice item for 1 has a quantity of 2.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-blue remaining picks"
        And 1 invoice item for 1.50 has a quantity of 3.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-red remaining picks"
        And 1 invoice item for 0.50 has a quantity of 1.00 and the description "Order: O-001, TN: O-001 | SKU: test-product-yellow remaining picks"
        And the picking rate "Test Picking Rate" billed 6.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that only charges for remainder picks
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "3" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "3" for picks 2 to 3
        And the picking rate "Test Picking Rate" has a fee of "0.4" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 10 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 3 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick fee"
        And 1 invoice item for 6 has a quantity of 2.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 3"
        And 1 invoice item for 2.8 has a quantity of 7.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed 11.8 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate filtering by missing order tags with a single shipment within the period, with tags with lower and Upper case
        Given the picking rate "Test Picking Rate" applies when the order is not tagged as "B2B"
        And the picking rate "Test Picking Rate" applies when the order is also not tagged as "WHOLESALE"
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "b2b"
        And the order "O-001" of client "Test 3PL Client" is tagged as "wholesale"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items

    Scenario: Billing a picking rate filtering by missing order tags with a single shipment within the period, with tags with lower and Upper case
        Given the picking rate "Test Picking Rate" applies when the order is tagged as "B2B"
        And the picking rate "Test Picking Rate" applies when the order is also tagged as "WHOLESALE"
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "b2b"
        And the order "O-001" of client "Test 3PL Client" is tagged as "wholesale"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And 1 invoice item for 0.00 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick fee"
        And the picking rate "Test Picking Rate" billed 2.05 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate filtering by missing order tags with a single shipment within the period, with tags with lower and Upper case
        Given the picking rate "Test Picking Rate" applies when the order is tagged as "b2b"
        And the picking rate "Test Picking Rate" applies when the order is also tagged as "wholesale"
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the order "O-001" of client "Test 3PL Client" is tagged as "WHOLESALE"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 2.05 has a quantity of 1.00 and the description "Flat fee for order number O-001"
        And 1 invoice item for 0.00 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick fee"
        And the picking rate "Test Picking Rate" billed 2.05 for the "FedEx" shipment on the date "2023-05-01"
