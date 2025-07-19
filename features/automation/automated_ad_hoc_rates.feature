@automation @orders
Feature: Charge ad hoc rates using automations
    As a 3PL owner
    I want to automate charging ad hoc rates to my clients
    So that I can capture billable operations when they happen.

    Background:
        And a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a sales channel named "punkrock.shopify.com"
        And the customer "Test 3PL Client" has an SKU "test-product-green" named "Test Product Green" weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-orange" named "Test Product Orange" weighing 8.49
        And a customer called "Another 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Another 3PL Client" has a sales channel named "heavymetal.shopify.com"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And an ad hoc rate "Manual labeling" on rate card "Test Rate Card"

    Scenario: Charge an ad hoc rate on receiving a purchase order by units
        Given the ad hoc rate "Manual labeling" charges 0.50 by "units" with the description
            """
            Labeling eaches as a special project per the client's explicit request.
            """
        And an order automation named "Charge for labeling" owned by "Test 3PL" is enabled
        And the automation is triggered when a purchase order is closed
        And the automation charges the ad hoc rate "Manual labeling"
        And the client "Test 3PL Client" has a pending purchase order "PO-001" for 100 of the SKU "test-product-green"
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the purchase order "PO-001" is received by the warehouse "Test Warehouse" into the location "Bin-0001"
        And the purchase order "PO-001" is closed
        Then the client "Test 3PL Client" should have a "Manual labeling" ad hoc charge for 50.00 on 100 "units"
        And the client "Test 3PL Client" should have a balance of 450.00
        And the purchase order "PO-001" has a log entry by "Roger" that reads
            """
            100 of SKU test-product-green received.
            """
        And the authenticated user is "roger+test_3pl@packiyo.com"

    Scenario: Charge an ad hoc rate on receiving a purchase order by hours
        Given the ad hoc rate "Manual labeling" charges 20.00 by "hours" with the description
            """
            Labeling eaches as a special project per the client's explicit request.
            """
        And an order automation named "Charge for labeling" owned by "Test 3PL" is enabled
        And the automation is triggered when a purchase order is closed
        And the automation charges the ad hoc rate "Manual labeling"
        And the client "Test 3PL Client" has a pending purchase order "PO-001" for 1000 of the SKU "test-product-green"
        And the purchase order requires 1000 of the SKU "test-product-purple"
        And the purchase order requires 1000 of the SKU "test-product-orange"
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When the purchase order "PO-001" is received by the warehouse "Test Warehouse" into the location "Bin-0001"
        And it took 150 "minutes" to receive the purchase order
        And the purchase order "PO-001" is closed
        Then the client "Test 3PL Client" should have a "Manual labeling" ad hoc charge for 50.00 on 2.5 "hours"
        And the client "Test 3PL Client" should have a balance of 450.00
        And the purchase order "PO-001" has a log entry by "Roger" that reads
            """
            1000 of SKU test-product-orange received.
            """
        And the purchase order "PO-001" has a log entry by "Roger" that reads
            """
            1000 of SKU test-product-purple received.
            """
        And the purchase order "PO-001" has a log entry by "Roger" that reads
            """
            1000 of SKU test-product-green received.
            """
        And the authenticated user is "roger+test_3pl@packiyo.com"
