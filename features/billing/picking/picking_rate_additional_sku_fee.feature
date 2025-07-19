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

    Scenario: Billing a picking rate that charges first pick and sku additional fees, that contains multiple SKUs
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1" for each additional SKU pick
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1.25 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And the picking rate "Test Picking Rate" billed 2.25 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: When adding kit product and the same product into the order, generates charges with no issues
        Given the customer "Test 3PL" has the feature flag "App\Features\FirstPickFeeFix" off
        And the picking rate "Test Picking Rate" has a flat fee of "22"
        And the picking rate "Test Picking Rate" has a fee of "0" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "3" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "3" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" requires 1 units of SKU "test-kit-purple"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items

    Scenario: When adding kit product and the same product into the order, generates charges with no issues
        Given the customer "Test 3PL" has the feature flag "App\Features\FirstPickFeeFix" on
        And the picking rate "Test Picking Rate" has a flat fee of "22"
        And the picking rate "Test Picking Rate" has a fee of "0" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "3" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "3" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" requires 1 units of SKU "test-kit-purple"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items

    Scenario: Billing a picking rate that charges first pick and sku additional, that contains a single SKU gets one charge
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And the picking rate "Test Picking Rate" billed 1.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario Outline: Billing a picking rate, that contains two different SKUs
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" <has_a_flat_fee> a flat fee of "<flat_fee_value>"
        And the picking rate "Test Picking Rate" <has_first_pick_fee> a fee of "<first_pick_fee_value>" for the first pick of an order
        And the picking rate "Test Picking Rate" <has_a_remainder_fee> of "<remainder_fee_value>" for the remainder picks of an order
        And the picking rate "Test Picking Rate" <has_a_additional_sku_fee> of "1.25" for each additional SKU pick
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the order "O-001" requires 1 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have <invoice_items> invoice items
        And 1 invoice item for <first_pick_fee_value> has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for <flat_fee_value> has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for <additional_sku_value> has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for <remainder_fee_values_red> has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And 1 invoice item for <remainder_fee_values_blue> has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | has_first_pick_fee | first_pick_fee_value | has_a_flat_fee | flat_fee_value | has_a_remainder_fee | remainder_fee_value | invoice_items | charge_value | remainder_fee_values_blue | remainder_fee_values_red | has_a_additional_sku_fee | additional_sku_value |
            | has                | 1.5                  | does not have  | 0              | has a fee           | 0.5                 | 2             | 2            | 0.5                       | 0                        | does not have a fee      | 0                    |
            | does not have      | 0                    | does not have  | 0              | has a fee           | 0.5                 | 2             | 0.5          | 0.5                       | 0                        | does not have a fee      | 0                    |
            | has                | 1.5                  | has            | 1              | does not have a fee |                     | 2             | 2.5          | 0                         | 0                        | does not have a fee      | 0                    |
            | does not have      | 0                    | has            | 1              | has a fee           | 0.5                 | 3             | 1.5          | 0.5                       | 0                        | does not have a fee      | 0                    |
            | has                | 1.5                  | does not have  | 0              | has a fee           | 0.5                 | 2             | 2            | 0.5                       | 0                        | does not have a fee      | 0                    |
            | has                | 1.5                  | has            | 1              | does not have a fee |                     | 3             | 3.75         | 0                         | 0                        | has a fee                | 1.25                 |
            | has                | 1.5                  | has            | 1              | does not have a fee |                     | 3             | 3.75         | 0                         | 0                        | has a fee                | 1.25                 |

    Scenario: Billing a picking rate that charges first pick, additional SKUs and remaining pick fees, that contains two SKUs. Only first pick and additional SKUs are charged
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the order "O-001" requires 1 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And the picking rate "Test Picking Rate" billed 2.75 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick, additional SKUs and remaining pick fees, that contains two SKUs. All fees are charged
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for 1 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And 1 invoice item for 1 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed 4.75 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges first pick, additional SKUs and remaining pick fees, that contains two SKUs. Only first pik and additional SKUs are charged
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 5 invoice items
        And 1 invoice item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for 1 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And 1 invoice item for 1 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed 5.75 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges additional SKUs and remaining pick fees, that contains two SKUs. Only first pik and additional SKUs are charged
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" has a fee of "0.50" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for 1 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And 1 invoice item for 1 has a quantity of 2 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed 3.25 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a picking rate that charges additional SKUs fees, that contains two SKUs. Only first pik and additional SKUs are charged
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a fee of "1.25" for each additional SKU pick
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the order "O-001" requires 1 units of SKU "test-product-blue"
        And the order "O-001" requires 1 units of SKU "test-product-yellow"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for 1.25 has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-yellow first pick of additional SKU"
        And the picking rate "Test Picking Rate" billed 2.5 for the "FedEx" shipment on the date "2023-05-01"

    Scenario Outline: : Billing a picking rate that charges first pick, additional SKUs and remaining pick fees, that contains two SKUs. One SKU contains 1 product, another contains 3 products
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "1"
        And the picking rate "Test Picking Rate" <has_first_pick_fee> a fee of "<first_pick_fee_value>" for the first pick of an order
        And the picking rate "Test Picking Rate" <has_a_additional_sku_fee> of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" <has_a_remainder_fee> of "<remainder_fee_value>" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-red"
        And the order "O-001" requires 3 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have <invoice_items> invoice items
        And 1 invoice item for 1 has a quantity of 1 and the description "Flat fee for order number O-001"
        And 1 invoice item for <first_pick_fee_value> has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for <additional_sku_value> has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for <remainder_fee_values_blue> has a quantity of <remainder_fee_quantity> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | has_a_remainder_fee | remainder_fee_value | invoice_items | charge_value | remainder_fee_values_blue | remainder_fee_quantity | has_a_additional_sku_fee | additional_sku_value | has_first_pick_fee | first_pick_fee_value |
            | has a fee           | 0.5                 | 4             | 4.75         | 1                         | 2                      | has a fee                | 1.25                 | has                | 1.5                  |
            | does not have a fee |                     | 3             | 3.75         | 0                         | 1                      | has a fee                | 1.25                 | has                | 1.5                  |
            | does not have a fee |                     | 2             | 2.5          | 0                         | 1                      | does not have a fee      | 0                    | has                | 1.5                  |
            | does not have a fee |                     | 2             | 1            | 0                         | 1                      | does not have a fee      | 0                    | does not have      | 0                    |

    Scenario Outline: : Billing a picking rate that charges first pick, additional SKUs and remaining pick fees, that contains two SKUs. One SKU contains 2 product, another contains 2 products
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" <has_flat_fee> flat fee of "1"
        And the picking rate "Test Picking Rate" has a fee of "1.5" for the first pick of an order
        And the picking rate "Test Picking Rate" <has_a_additional_sku_fee> of "1.25" for each additional SKU pick
        And the picking rate "Test Picking Rate" <has_reminder_fee> fee of "<remainder_fee_value>" for the remainder picks of an order
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-red"
        And the order "O-001" requires 2 units of SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have <invoice_items> invoice items
        And 1 invoice item for <flat_values> has a quantity of <flat_fee_quantity> and the description "Flat fee for order number O-001"
        And 1 invoice item for 1.5 has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red first pick fee"
        And 1 invoice item for <additional_sku_value> has a quantity of 1.00 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue first pick of additional SKU"
        And 1 invoice item for <remainder_fee_values_red> has a quantity of 1 and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-red remaining picks"
        And 1 invoice item for <remainder_fee_values_blue> has a quantity of <remainder_fee_quantity_blue> and the description "Order: O-001, TN: TN-O-001 | SKU: test-product-blue remaining picks"
        And the picking rate "Test Picking Rate" billed <charge_value> for the "FedEx" shipment on the date "2023-05-01"

        Examples:
            | has_flat_fee    | invoice_items | charge_value | flat_values | flat_fee_quantity | has_a_additional_sku_fee | additional_sku_value | remainder_fee_values_blue | remainder_fee_values_red | remainder_fee_quantity_blue | has_reminder_fee | remainder_fee_value |
            | has a           | 5             | 4.75         | 1           | 1                 | has a fee                | 1.25                 | 0.5                       | 0.5                      | 1                           | has a            | 0.5                 |
            | does not have a | 4             | 3.75         | 0           | 1                 | has a fee                | 1.25                 | 0.5                       | 0.5                      | 1                           | has a            | 0.5                 |
            | does not have a | 3             | 3            | 0           | 1                 | does not have a fee      | 0                    | 1                         | 0.5                      | 2                           | has a            | 0.5                 |
            | has a           | 2             | 2.5          | 0           | 1                 | does not have a fee      | 0                    | 0                         | 0                        | 2                           | does not have a  |                     |
