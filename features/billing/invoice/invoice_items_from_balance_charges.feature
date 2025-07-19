@3pl @billing @automation @invoice
Feature: Add invoice items for billing charges
    As a 3PL owner
    I want the billing charges on my client's balance on their invoice
    So that I can invoice them for those charges.

    Background:
        # The 3PL.
        And a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a location type called "Test Location Type"
        And the location "A-1" is of type "Test Location Type"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        # The 3PL client.
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a sales channel named "punkrock.shopify.com"
        And I will work with customer "Test 3PL Client"
        And the customer "Test 3PL Client" has an SKU "test-product-green" named "Test Product Green" weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-orange" named "Test Product Orange" weighing 8.49
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And I manually set 100 of "test-product-green" into "A-1" location
        And I manually set 100 of "test-product-purple" into "A-1" location
        And I manually set 100 of "test-product-orange" into "A-1" location
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"

    Scenario: Calculating an invoice with charges outside of the billing period
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-green"
        And an order automation named "Charge for packaging" owned by "Test 3PL" is enabled
        And the automation is triggered when an order is shipped
        And the automation charges 0.99 for each box of any kind shipped
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-04-30" with tracking "TN-O-001"
        And the client "Test 3PL Client" has a shipping box charge for 0.99 and quantity 2 for tracking "TN-O-001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-07"
        And the invoice is calculated in the background
        Then the invoice should not have any invoice items

    Scenario: Calculating an invoice with just one charge on one warehouse's balance
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-green"
        And an order automation named "Charge for packaging" owned by "Test 3PL" is enabled
        And the automation is triggered when an order is shipped
        And the automation charges 0.99 for each box of any kind shipped
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01" with tracking "TN-O-001"
        And the client "Test 3PL Client" has a shipping box charge for 0.99 and quantity 2 for tracking "TN-O-001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-07"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 0.99 has a quantity of 2 and the description "This is a shipping box charge."

    Scenario: Calculating an invoice based on charges debited by triggering an automation
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-green"
        And an order automation named "Charge for packaging" owned by "Test 3PL" is enabled
        And the automation is triggered when an order is shipped
        And the automation charges 0.99 for each box of any kind shipped
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the 3PL "Test 3PL" ships order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01" with tracking "TN-O-001"
        And any created charges share the timestamp of the corresponding shipment
        And I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-07"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 0.99 has a quantity of 1 and the description "Packaging for order O-001"
