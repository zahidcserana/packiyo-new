@3pl @billing @picking @charges
Feature: Billing for product picking on shipments
    As the owner of a 3PL business
    I want to be able to charge for picking with different fees
    So that I can charge my customers according to the labour involved in picking each shipment.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has the feature flag "App\Features\FirstPickFeeFix" on
        And a 3PL called "Test 3PL" was created on "2024-04-16"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the customer "Test 3PL Client" has an SKU "test-kit-purple" named "Test Kit Purple" priced at 17.99
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
        And the SKU "test-product-red" is added as a component to the kit product with quantity of 2
        And the customer "Test 3PL Client" has an SKU "test-kit-green" named "Test Kit Green" priced at 14.49
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
        And the SKU "test-product-yellow" is added as a component to the kit product with quantity of 2
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"

    Scenario: Billing a picking rate that charges first pick and flat fee, that contains a single SKU gets two charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 2.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick and remainder fee, that contains a single SKU gets one charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And the picking rate "Test Picking Rate" billed 1.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick and flat fee, that contains a two SKU gets two charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 2.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick and remainder fee, that contains a one SKU gets two charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 0.50 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And the picking rate "Test Picking Rate" billed 2 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges remainder fee, that contains one SKU
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 0.5 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And the picking rate "Test Picking Rate" billed 0.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges flat fee, that contains a four SKU gets one charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the customer "Test 3PL Client" got the order number "O-001" for 4 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 1 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick remainder pick and flat fee, that contains a two SKU gets two charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 4 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for 1.50 has a quantity of 3.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And the picking rate "Test Picking Rate" billed 4 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that only charges first pick, that contains multiple SKUs only gets one charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for the first pick of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the order "O-001" requires 2 units of SKU "test-product-yellow"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 1.25 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And the picking rate "Test Picking Rate" billed 1.25 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that only charges first pick, that contains two SKUs only gets one fee
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for the first pick of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 1.25 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And the picking rate "Test Picking Rate" billed 1.25 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges flat fee, and first pick fees, that contains two SKUs. All fees are charged
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And the picking rate "Test Picking Rate" billed 2.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges remaining pick fees, that contains two SKUs. Only first pik and additional SKUs are charged
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 1 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And 1 invoice item for 1.5 has a quantity of 3 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed 2.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges flat fee, that contains two SKUs. Only first pik and additional SKUs are charged
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And the picking rate "Test Picking Rate" billed 1 for the "FedEx" shipment on the date "2023-05-01"
