@3pl @billing @picking @sp-rates
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
        And the warehouse "Test Warehouse" has a pickable location called "A-001"
        And the warehouse "Test Warehouse" has a location type called "Test Location Type"
        And the location "A-001" is of type "Test Location Type"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-blue" into "A-001" location
        And I manually set 100 of "test-product-red" into "A-001" location
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And the SKU "test-product-red" of client "Test 3PL Client" is tagged as "FBA-Product"
        And the SKU "test-product-red" of client "Test 3PL Client" is tagged as "Bubble"
        And the SKU "test-product-blue" of client "Test 3PL Client" is tagged as "Remove-Label"
        And the SKU "test-product-blue" of client "Test 3PL Client" is tagged as "Warning"
        And the SKU "test-product-blue" of client "Test 3PL Client" is tagged as "Fragile"

    Scenario: Billing a picking rate within the period (Scenario 1)
        Given a picking rate "ACCT-MGMT" on rate card "Test Rate Card"
        And the picking rate "ACCT-MGMT" has a flat fee of "3.99"
        And the picking rate "ACCT-MGMT" applies when the order is tagged as "SP-Edit"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "SP-Edit"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 3.99 has a quantity of 1 and the description "Flat fee for order number O-001"

    Scenario: Billing a picking rate within the period (Scenario 2)
        Given a picking rate "FFLL-TAPE" on rate card "Test Rate Card"
        And the picking rate "FFLL-TAPE" has a flat fee of "0.25"
        And the picking rate "FFLL-TAPE" applies when the order is tagged as "SP-Tape"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-blue"
        And the order "O-002" of client "Test 3PL Client" is tagged as "SP-Tape"
        And the 3PL "Test 3PL" shipped order "O-002" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 0.25 has a quantity of 1 and the description "Flat fee for order number O-002"

    Scenario: Billing a picking rate within the period (Scenario 3)
        Given a picking rate "FFLL-STKR" on rate card "Test Rate Card"
        And the picking rate "FFLL-STKR" has a flat fee of "0.1"
        And the picking rate "FFLL-STKR" applies when the order is tagged as "SP-Sticker"
        And the customer "Test 3PL Client" got the order number "O-003" for 1 SKU "test-product-blue"
        And the order "O-003" of client "Test 3PL Client" is tagged as "SP-Sticker"
        And the 3PL "Test 3PL" shipped order "O-003" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 0.1 has a quantity of 1 and the description "Flat fee for order number O-003"

    Scenario: Billing a picking rate within the period (Scenario 4)
        Given a picking rate "FFLL-INST" on rate card "Test Rate Card"
        And the picking rate "FFLL-INST" has a flat fee of "0.1"
        And the picking rate "FFLL-INST" applies when the order is tagged as "SP-Insert"
        And the customer "Test 3PL Client" got the order number "O-004" for 1 SKU "test-product-red"
        And the order "O-004" of client "Test 3PL Client" is tagged as "SP-Insert"
        And the 3PL "Test 3PL" shipped order "O-004" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 0.1 has a quantity of 1 and the description "Flat fee for order number O-004"

    Scenario: Billing a picking rate within the period (Scenario 5)
        Given a picking rate "FFLL-FBA" on rate card "Test Rate Card"
        And the picking rate "FFLL-FBA" has a flat fee of "1.05"
        And the picking rate "FFLL-FBA" applies when the product is tagged as "FBA-Product"
        And the picking rate "FFLL-FBA" has a fee of "0.35" for each additional SKU pick
        And the customer "Test 3PL Client" got the order number "O-005" for 10 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-005" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 1.05 has a quantity of 1 and the description "Flat fee for order number O-005"

    Scenario: Billing a picking rate within the period (Scenario 6)
        Given a picking rate "FFLL-FBA-INB" on rate card "Test Rate Card"
        And the picking rate "FFLL-FBA-INB" has a flat fee of "19.99"
        And the picking rate "FFLL-FBA-INB" applies when the order is tagged as "SP-FBA-Order"
        And the customer "Test 3PL Client" got the order number "O-006" for 1 SKU "test-product-blue"
        And the order "O-006" of client "Test 3PL Client" is tagged as "SP-FBA-Order"
        And the 3PL "Test 3PL" shipped order "O-006" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 19.99 has a quantity of 1 and the description "Flat fee for order number O-006"

    Scenario: Billing a picking rate within the period (Scenario 7)
        Given a picking rate "FFLL-BBLL" on rate card "Test Rate Card"
        And the picking rate "FFLL-BBLL" has a flat fee of "0.55"
        And the picking rate "FFLL-BBLL" applies when the product is tagged as "Bubble"
        And the customer "Test 3PL Client" got the order number "O-007" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-007" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 0.55 has a quantity of 1 and the description "Flat fee for order number O-007"

    Scenario: Billing a picking rate within the period (Scenario 8)
        Given a picking rate "FFLL-LBRM" on rate card "Test Rate Card"
        And the picking rate "FFLL-LBRM" has a flat fee of "0.4"
        And the picking rate "FFLL-LBRM" applies when the product is tagged as "Remove-Label"
        And the customer "Test 3PL Client" got the order number "O-008" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-008" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 0.4 has a quantity of 1 and the description "Flat fee for order number O-008"

    Scenario: Billing a picking rate within the period (Scenario 9)
        Given a picking rate "FFLL-WRNG" on rate card "Test Rate Card"
        And the picking rate "FFLL-WRNG" has a flat fee of "0.15"
        And the picking rate "FFLL-WRNG" applies when the product is tagged as "Warning"
        And the customer "Test 3PL Client" got the order number "O-009" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-009" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 0.15 has a quantity of 1 and the description "Flat fee for order number O-009"

    Scenario: Billing a picking rate within the period (Scenario 10)
        Given a picking rate "FFLL-FRGL" on rate card "Test Rate Card"
        And the picking rate "FFLL-FRGL" has a flat fee of "0.15"
        And the picking rate "FFLL-FRGL" applies when the product is tagged as "Fragile"
        And the customer "Test 3PL Client" got the order number "O-010" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-010" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items

    Scenario: Billing a picking rate within the period (Scenario 11)
        Given a picking rate "FFLL-RTRN" on rate card "Test Rate Card"
        And the picking rate "FFLL-RTRN" has a flat fee of "3.49"
        And the picking rate "FFLL-RTRN" applies when the order is tagged as "Return"
        And the customer "Test 3PL Client" got the order number "O-011" for 1 SKU "test-product-blue"
        And the order "O-011" of client "Test 3PL Client" is tagged as "Return"
        And the 3PL "Test 3PL" shipped order "O-011" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And 1 invoice item for 3.49 has a quantity of 1 and the description "Flat fee for order number O-011"

    Scenario: Billing a picking rate within the period (Scenario 12)
        Given a picking rate "FFLL-FLRTRNSFR" on rate card "Test Rate Card"
        And the picking rate "FFLL-FLRTRNSFR" has a flat fee of "5.99"
        And the picking rate "FFLL-FLRTRNSFR" applies when the order is tagged as "XFloor"
        And the customer "Test 3PL Client" got the order number "O-012" for 1 SKU "test-product-blue"
        And the customer "Test 3PL Client" got the order number "O-012b" for 3 SKU "test-product-blue"
        And the customer "Test 3PL Client" got the order number "O-012c" for 5 SKU "test-product-blue"
        And the order "O-012" of client "Test 3PL Client" is tagged as "XFloor"
        And the order "O-012b" of client "Test 3PL Client" is tagged as "XFloor"
        And the 3PL "Test 3PL" shipped order "O-012" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        And the 3PL "Test 3PL" shipped order "O-012b" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        And the 3PL "Test 3PL" shipped order "O-012c" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 4 invoice items
        And 1 invoice item for 5.99 has a quantity of 1 and the description "Flat fee for order number O-012"
        And 1 invoice item for 5.99 has a quantity of 1 and the description "Flat fee for order number O-012b"

    Scenario: Billing a picking rate within the period (Scenario 14)
        Given a picking rate "FFLL" on rate card "Test Rate Card"
        And the picking rate "FFLL" applies when the order is not tagged as "SP-FBA-Order"
        And the picking rate "FFLL" applies when the order is not tagged as "SP-Freight"
        And the picking rate "FFLL" has a fee of "1.79" for the first pick of an order
        And the picking rate "FFLL" has a fee of "0.5" for each additional SKU pick
        And the customer "Test 3PL Client" got the order number "O-014" for 1 SKU "test-product-blue"
        And the order "O-014" requires 2 units of SKU "test-product-red"
        And the order "O-014" of client "Test 3PL Client" is tagged as "SP-Not-Tagged"
        And the 3PL "Test 3PL" shipped order "O-014" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 3 invoice items
        And 1 invoice item for 1.79 has a quantity of 1 and the description "Order: O-014, TN: TN-O-014 | SKU: test-product-blue first pick fee"
        And 1 invoice item for 1.79 has a quantity of 1 and the description "Order: O-014, TN: TN-O-014 | SKU: test-product-red first pick fee"
        And 1 invoice item for 0.5 has a quantity of 1 and the description "Order: O-014, TN: TN-O-014 | SKU: test-product-red first pick of additional SKU"

    Scenario: Billing multiple picking rates within the period with overlapping rates allowed
        And a picking rate "FFLL-FLRTRNSFR" on rate card "Test Rate Card"
        And a picking rate "FFLL-RTRN" on rate card "Test Rate Card"
        And the picking rate "FFLL-FLRTRNSFR" has a flat fee of "5.99"
        And the picking rate "FFLL-FLRTRNSFR" applies when the order is tagged as "XFloor"
        And the picking rate "FFLL-RTRN" has a flat fee of "3.49"
        And the picking rate "FFLL-RTRN" applies when the order is tagged as "Return"
        And the instance has the feature flag "App/Features/AllowOverlappingRates" enabled
        And the customer "Test 3PL Client" got the order number "O-015" for 5 SKU "test-product-blue"
        And the order "O-015" requires 5 units of SKU "test-product-red"
        And the order "O-015" of client "Test 3PL Client" is tagged as "XFloor"
        And the order "O-015" of client "Test 3PL Client" is tagged as "Return"
        And the 3PL "Test 3PL" shipped order "O-015" for its client "Test 3PL Client" through "FedEx" on the "2024-08-30"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-08-20" to "2024-08-31"
        And the invoice is calculated in the background
        Then the invoice should have 6 invoice items
        And 1 invoice item for 5.99 has a quantity of 1 and the description "Flat fee for order number O-015"
        And 1 invoice item for 3.49 has a quantity of 1 and the description "Flat fee for order number O-015"
