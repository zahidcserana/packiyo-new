@3pl @billing  @invoice
Feature: Invoice status
    As a 3PL owner
    I want to know which status my invoice is at during calculation
    So that i can know at which point my invoice is

    Background:
        # The 3PL.
        And a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test 3PL" has the feature flag "App\Features\CoPilot" on
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        # The 3PL client.
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a sales channel named "punkrock.shopify.com"
        And the customer "Test 3PL Client" has an SKU "test-product-green" named "Test Product Green" weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-orange" named "Test Product Orange" weighing 8.49
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"

    Scenario: Invoice reaches pending status
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-green"
        And an order automation named "Charge for packaging" owned by "Test 3PL" is enabled
        And the automation is triggered when an order is shipped
        And the automation charges 0.99 for each box of any kind shipped
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-04-30" with tracking "TN-O-001"
        And the client "Test 3PL Client" has a shipping box charge for 0.99 and quantity 2 for tracking "TN-O-001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-07"
        Then invoice has status "pending"

    Scenario: Invoice reaches done status
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
        Then invoice has status "done"

    Scenario: Invoice reaches failed status
        Given the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-green"
        And an order automation named "Charge for packaging" owned by "Test 3PL" is enabled
        And the automation is triggered when an order is shipped
        And the automation charges 0.99 for each box of any kind shipped
        And the client "Test 3PL Client" has a balance of 500.00
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01" with tracking "TN-O-001"
        And the client "Test 3PL Client" has a shipping box charge for 0.99 and quantity 2 for tracking "TN-O-001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-07"
        And the invoice spent "2" days without being calculated
        Then invoice has status "failed"
