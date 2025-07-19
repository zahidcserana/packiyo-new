@3pl @billing @picking @mongo
Feature: Billing for product picking on shipments
    As the owner of a 3PL business
    I want to be able to charge for picking using different strategies

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" on
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

    Scenario: Billing a default picking rate with a single shipment within the period with Wallet feature enable.
        Should not generate invoice items
        Given the picking rate "Test Picking Rate" applies when no other rate matches
        And the picking rate "Test Picking Rate" has a flat fee of "2.05"
        And the customer "Test 3PL Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items
