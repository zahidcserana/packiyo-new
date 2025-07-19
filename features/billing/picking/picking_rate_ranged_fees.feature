@3pl @billing @picking @charges
Feature: Billing for product picking on shipments
    As the owner of a 3PL business
    I want to be able to charge for picking with different fees
    So that I can charge my customers according to the labour involved in picking each shipment.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a 3PL called "Test 3PL" was created on "2024-04-16"
        And the customer "Test 3PL" has the feature flag "App\Features\FirstPickFeeFix" on
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a shipping box named '6" x 6" x 6" Brown Box'
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

    Scenario: Billing a picking rate that charges first pick and range fees, that contains a four SKU gets two charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 4
        And the customer "Test 3PL Client" got the order number "O-001" for 4 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 2.25 has a quantity of 3.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 4"
        And the picking rate "Test Picking Rate" billed 3.75 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick, range and remainder fees, that contains a four SKU gets three charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 3
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 4 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1.5 has a quantity of 2.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 3"
        And 1 invoice item for 0.5 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And the picking rate "Test Picking Rate" billed 3.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick and range fees, that contains a four SKU gets three charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 2
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 4 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 0.75 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 2"
        And 1 invoice item for 1 has a quantity of 2.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And the picking rate "Test Picking Rate" billed 3.25 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick, additional SKUs and range pick fees, that contains two SKUs. One SKU contains 1 product, another contains 3 products gets 3 charges
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 4
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for 1.5 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 4"
        And the picking rate "Test Picking Rate" billed 4.25 for the "FedEx" shipment on the date "2023-05-01"

    Scenario Outline: Billing a picking rate that charges first pick, additional SKUs and one range pick fees, that contains two SKUs.
    One SKU contains 3 products, another contains 3 products does not get charged remaining fees
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" <has_reminder_fee> fee of "<remainder_fee_value>" for the remainder picks of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 6
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for 1.5 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 6"
        And 1 invoice item for 1.5 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 6"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | has_reminder_fee | remainder_fee_value | charge_value |
            | does not have a  |                     | 5.75         |
            | has a            | 0.5                 | 5.75         |

    Scenario Outline: Billing a picking rate that charges first pick, additional SKUs and multiple range pick fees, that contains two SKUs.
    One SKU contains 3 products, another contains 3 products does not get charged remaining fees
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" <has_reminder_fee> fee of "<remainder_fee_value>" for the remainder picks of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 3
        And the picking rate "Test Picking Rate" has a fee of "0.25" for picks 4 to 6
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for 1.5 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 3"
        And 1 invoice item for 0.5 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 4 to 6"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | has_reminder_fee | remainder_fee_value | charge_value |
            | does not have a  |                     | 4.75         |
            | has a            | 0.5                 | 4.75         |

    Scenario: Billing a picking rate that charges first pick and range pick fees, that contains two SKUs. One SKU contains 2 products, another contains 2 products gets 2 charges
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 4
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 0.75 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 4"
        And 1 invoice item for 1.5 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 4"
        And the picking rate "Test Picking Rate" billed 3.75 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick, remaining picks and range pick fees, that contains two SKUs. One SKU contains 2 products, another contains 2 products gets 3 charges
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 3
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 0.75 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 3"
        And 1 invoice item for 0.75 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 3"
        And 1 invoice item for 0.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed 3.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges additional sku pick and range pick fees, that contains two SKUs. One SKU contains 2 products, another contains 2 products gets 2 charges
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 4
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for 0.75 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 4"
        And 1 invoice item for 0.75 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 4"
        And the picking rate "Test Picking Rate" billed 2.75 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges range pick fees and remaining pick, that contains two SKUs. One SKU contains 2 products, another contains 2 products gets 2 charges
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 2
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 0.75 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 2"
        And 1 invoice item for 1 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed 1.75 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges multiple range pick fees , that contains two SKUs. One SKU contains 2 products, another contains 2 products gets 3 charges
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1" for picks 2 to 3
        And the picking rate "Test Picking Rate" has a fee of "2" for picks 4 to 4
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 1 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 3"
        And 1 invoice item for 1 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 3"
        And 1 invoice item for 2 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 4 to 4"
        And the picking rate "Test Picking Rate" billed 4 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges multiple range pick fees and first pick fee, that contains two SKUs. One SKU contains 2 products, another contains 2 products gets 3 charges
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1" for picks 2 to 3
        And the picking rate "Test Picking Rate" has a fee of "2" for picks 4 to 4
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 3"
        And 1 invoice item for 1 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 3"
        And 1 invoice item for 2 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 4 to 4"
        And the picking rate "Test Picking Rate" billed 5.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario Outline: : Billing a picking rate that charges first pick, and remaining pick fees, that contains two SKUs. One SKU contains 1 product, another contains 3 products
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" <has_reminder_fee> fee of "<remainder_fee_value>" for the remainder picks of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks <range_to> to <range_from>
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have <invoice_items> invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for <range_pick_fee_values_blue> has a quantity of <range_pick_fee_quantity_blue> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks <range_to> to <range_from>"
        And 1 invoice item for <remainder_fee_values_blue> has a quantity of <remainder_fee_quantity_blue> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | range_to | range_from | invoice_items | charge_value | range_pick_fee_values_blue | range_pick_fee_quantity_blue | has_reminder_fee | remainder_fee_value | remainder_fee_values_blue | remainder_fee_quantity_blue |
            | 2        | 3          | 3             | 3.5          | 1.5                        | 2                            | has a            | 0.5                 | 0.5                       | 1                           |
            | 2        | 4          | 2             | 3.75         | 2.25                       | 3                            | does not have a  |                     | 0                         | 1                           |

    Scenario Outline: Billing a picking rate that charges range pick fees, that contains two SKUs. One SKU contains 3 products, another contains 3 products
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" <has_first_pick> a fee of "<first_pick_fee_value>" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 6
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have <invoice_items> invoice items
        And 1 invoice item for <first_pick_value> has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for <range_red_fee_value> has a quantity of <range_red_fee_quantity> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 6"
        And 1 invoice item for <range_blue_fee_value> has a quantity of <range_blue_fee_quantity> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 2 to 6"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | has_first_pick | first_pick_fee_value | charge_value | first_pick_value | range_red_fee_value | range_red_fee_quantity | invoice_items | range_blue_fee_value | range_blue_fee_quantity |
            | does not have  |                      | 3.75         | 0                | 1.5                 | 2                      | 3             | 2.25                 | 3                       |
            | has            | 1.5                  | 5.25         | 1.5              | 1.5                 | 2                      | 3             | 2.25                 | 3                       |

    Scenario Outline: Billing a picking rate that charges multiple range pick and first pick fees, that contains two SKUs. One SKU contains 3 products, another contains 3 products
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" <has_first_pick> a fee of "<first_pick_fee_value>" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 3
        And the picking rate "Test Picking Rate" has a fee of "0.25" for picks 4 to 6
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have <invoice_items> invoice items
        And 1 invoice item for <first_pick_value> has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for <range_red_fee_value> has a quantity of <range_red_fee_quantity> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 3"
        And 1 invoice item for <range_blue_fee_value> has a quantity of <range_blue_fee_quantity> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 4 to 6"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | has_first_pick | first_pick_fee_value | charge_value | first_pick_value | range_red_fee_value | range_red_fee_quantity | invoice_items | range_blue_fee_value | range_blue_fee_quantity |
            | does not have  |                      | 2.25         | 0                | 1.5                 | 2                      | 3             | 0.75                 | 3                       |
            | has            | 1.5                  | 3.75         | 1.5              | 1.5                 | 2                      | 3             | 0.75                 | 3                       |

    Scenario Outline: Billing a picking rate that charges multiple range pick and flat fees, that contains two SKUs. One SKU contains 3 products, another contains 3 products
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" <has_flat_fee> flat fee of "1"
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 2 to 3
        And the picking rate "Test Picking Rate" has a fee of "0.25" for picks 4 to 6
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have <invoice_items> invoice items
        And 1 invoice item for <flat_fee_value> has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for <range_red_fee_value> has a quantity of <range_red_fee_quantity> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red picks 2 to 3"
        And 1 invoice item for <range_blue_fee_value> has a quantity of <range_blue_fee_quantity> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue picks 4 to 6"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | has_flat_fee    | charge_value | range_red_fee_value | range_red_fee_quantity | invoice_items | range_blue_fee_value | range_blue_fee_quantity | flat_fee_value |
            | does not have a | 2.25         | 1.5                 | 2                      | 3             | 0.75                 | 3                       | 0              |
            | has a           | 3.25         | 1.5                 | 2                      | 4             | 0.75                 | 3                       | 1              |

    Scenario: Picking range fees, with multiple skus with more than one package dont cause error for index not found when generating invoice line items
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 1 to 2
        And the picking rate "Test Picking Rate" has a fee of "0.25" for picks 3 to 6
        And the picking rate "Test Picking Rate" has a fee of "0.3" for picks 7 to 10
        And the picking rate "Test Picking Rate" has a fee of "0.5" for picks 11 to 15
        And the customer "Test 3PL Client" got the order number "O-001" for 6 SKU "test-product-red"
        And the order "O-001" requires 6 units of SKU "test-product-blue"
        And the order number "O-001" for 3pl client "Test 3PL Client" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 6 | test-product-red | 001|
            | #1 | 6" x 6" x 6" Brown Box | 4 | test-product-blue | 001|
            | #2 | 6" x 6" x 6" Brown Box | 2 | test-product-blue | 001|
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001" the order shipped event is dispatch
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-04-01" to "2024-05-01"
        And the invoice is calculated in the background
        Then the invoice should have 5 invoice items

    Scenario: Picking range fees, with multiple skus with one package dont cause error for index not found when generating invoice line items
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 1 to 2
        And the picking rate "Test Picking Rate" has a fee of "0.25" for picks 3 to 6
        And the picking rate "Test Picking Rate" has a fee of "0.3" for picks 7 to 10
        And the picking rate "Test Picking Rate" has a fee of "0.5" for picks 11 to 15
        And the customer "Test 3PL Client" got the order number "O-001" for 6 SKU "test-product-red"
        And the order "O-001" requires 6 units of SKU "test-product-blue"
        And the order number "O-001" for 3pl client "Test 3PL Client" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-red | 001|
            | #2 | 6" x 6" x 6" Brown Box | 2 | test-product-red | 001|
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-blue | 001|
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-blue | 002|
            | #2 | 6" x 6" x 6" Brown Box | 2 | test-product-blue | 002|
            | #2 | 6" x 6" x 6" Brown Box | 2 | test-product-red | 002|
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001" the order shipped event is dispatch
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-04-01" to "2024-05-01"
        And the invoice is calculated in the background
        Then the invoice should have 8 invoice items

    Scenario: Picking range fees, with multiple skus with more than one package dont cause error for index not found when generating invoice line items
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "0.75" for picks 1 to 2
        And the picking rate "Test Picking Rate" has a fee of "0.25" for picks 3 to 6
        And the picking rate "Test Picking Rate" has a fee of "0.3" for picks 7 to 10
        And the picking rate "Test Picking Rate" has a fee of "0.5" for picks 11 to 15
        And the customer "Test 3PL Client" got the order number "O-001" for 6 SKU "test-product-red"
        And the order "O-001" requires 6 units of SKU "test-product-blue"
        And the order number "O-001" for 3pl client "Test 3PL Client" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-red | 001|
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-red | 001|
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-blue | 001|
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-blue | 002|
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-blue | 002|
            | #1 | 6" x 6" x 6" Brown Box | 2 | test-product-red | 002|
        When the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2024-04-15" with tracking "TN-O-001" the order shipped event is dispatch
        And I calculate an invoice for customer "Test 3PL Client" for the period "2024-04-01" to "2024-05-01"
        And the invoice is calculated in the background
        Then the invoice should have 8 invoice items
