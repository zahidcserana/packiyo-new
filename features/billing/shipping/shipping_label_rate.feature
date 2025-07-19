@3pl @billing @shipping
Feature: Billing for storage by location over different periods
    As the owner of a 3PL business
    I want to be able to charge shipping label rates by carrier, method, or tags
    So that I can charge my customers according to each shipment's characteristics.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground Shipping" for "Test 3PL"
        And a shipping carrier "DHL" and a shipping method "Ground Shipping" for "Test 3PL"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the customer "Test 3PL Client" has an SKU "test-kit-purple" named "Test Kit Purple" priced at 17.99
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
        And the SKU "test-product-red" is added as a component to the kit product with quantity of 2
        And the customer "Test 3PL Client" has an SKU "test-kit-green" named "Test Kit Green" priced at 14.49
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
        And the SKU "test-product-yellow" is added as a component to the kit product with quantity of 2
        And the customer "Test 3PL Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        # And the customer "Test 3PL Client" got an order number "O-001"
        # And the customer "Test 3PL Client" got an order number "O-002"

    Scenario: Billing a default shipping label rate with no shipments within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-04-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should not have any invoice items

    Scenario: Billing a default shipping label rate with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a shipping label rate filtering by carrier and method with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a shipping label rate after when then carrier is deactivated with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When the shipping carrier "FedEx" is deactivated
        And I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items

    Scenario: Billing a shipping label rate after when then carrier is deactivated and moments later activated
    with same carrier and shipping method with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        And the shipping carrier "FedEx" is deactivated
        When a shipping carrier "FedEx" and a shipping method "Ground Shipping"
        And I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And invoice item correspond to carrier that was deactivated

    Scenario: Billing a shipping label rate when shipment generated after deactivating carrier with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate has a flat fee of "1.50"
        And the shipping carrier "FedEx" is deactivated
        And for a customer "Test 3PL" a shipping carrier "FedEx" and a shipping method "Ground Shipping"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items

    Scenario: Not Billing a shipping label rate when shipment set in rate is deactivated, but new shipment carrier is being set for order
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate has a flat fee of "1.50"
        And the shipping carrier "FedEx" is deactivated
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "DHL" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items

    Scenario: Not Billing a shipping label rate when shipment set in rate is deactivated and other method is used,
    but new shipment carrier is being set for order
        Given a shipping carrier "FedEx" contains shipping method "Air Shipping"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx" with shipping method "Ground Shipping"
        And the shipping label rate has a flat fee of "1.50"
        And the shipping carrier "FedEx" is deactivated
        And for a customer "Test 3PL" a shipping carrier "FedEx" and a shipping method "Air Shipping"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" with method "Air Shipping" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items

    Scenario: Billing a shipping label rate after reactivating a carrier with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        And the shipping carrier "FedEx" is deactivated
        And for a customer "Test 3PL" a shipping carrier "FedEx" and a shipping method "Ground Shipping"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-002" for its client "Test 3PL Client" through "FedEx" on the "2023-05-02"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"


    Scenario: Billing a shipping label rate filtering by order tags with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a shipping label rate for a carrier with all shipment methods within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx" to all shipment methods
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-002" for its client "Test 3PL Client" through "FedEx" on the "2023-05-02"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items

    Scenario: Billing a shipping label rate after reactivating for a carrier with all shipment methods within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx" to all shipment methods
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-002" for its client "Test 3PL Client" through "FedEx" on the "2023-05-02"
        And the shipping carrier "FedEx" is deactivated
        And for a customer "Test 3PL" a shipping carrier "FedEx" and a shipping method "Ground Shipping"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items

    Scenario: Billing a shipping label rate after reactivating a carrier with all shipment methods selected with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx" to all shipment methods
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        And the shipping carrier "FedEx" is deactivated
        And for a customer "Test 3PL" a shipping carrier "FedEx" and a shipping method "Air Shipping"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-002" for its client "Test 3PL Client" through "FedEx" on the "2023-05-02"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 2 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a shipping label rate for a carrier with all shipment methods and an order with another carrier within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx" to all shipment methods
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-red"
        And the 3PL "Test 3PL" shipped order "O-002" for its client "Test 3PL Client" through "DHL" on the "2023-05-02"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 0 invoice items

    Scenario: Billing a shipping label rate filtering by missing order tags with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the order is not tagged as "B2B"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2C"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a shipping label rate filtering by carrier and method, and order tags with a single shipment within the period
        Given the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2B"
        And the customer "Test 3PL Client" got the order number "O-002" for 1 SKU "test-product-red"
        And the order "O-002" of client "Test 3PL Client" is tagged as "B2B"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        And the 3PL "Test 3PL" shipped order "O-002" for its client "Test 3PL Client" through "DHL" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a default shipping label rate with a percentage of cost fee
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate charges 110% of the base cost of the labels
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        And the shipment of order "O-001" has a label with tracking "TN_O-001" which cost 1.25
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 1.38 has a quantity of 1.00 and the description "Shipment Number: TN_O-001 | FedEx via Ground Shipping, order no. O-001"
        And the shipping label rate "Test Shipping Label Rate" billed 1.38 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a shipping label rate filtering by order tags with a single shipment within the period and tags with lower and upper case
        Given the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "b2b"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a shipping label rate filtering by order tags with a single shipment within the period and tags with lower and upper case
        Given the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "b2b"
        And the shipping label rate has a flat fee of "1.50"
        And the customer "Test 3PL Client" got the order number "O-001" for 1 SKU "test-product-blue"
        And the order "O-001" of client "Test 3PL Client" is tagged as "B2b"
        And the 3PL "Test 3PL" shipped order "O-001" for its client "Test 3PL Client" through "FedEx" on the "2023-05-01"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And the shipping label rate "Test Shipping Label Rate" billed 1.50 for the "FedEx" shipment on the date "2023-05-01"

    Scenario: Billing a default shipping label rate with a percentage of cost fee, with multiple packages, only charges for the shipment and not the amount of packages
        Given the shipping label rate "Test Shipping Label Rate" applies when no other rate matches
        And the shipping label rate charges 110% of the base cost of the labels
        And the customer "Test 3PL Client" got the order number "O-001" for 2 SKU "test-product-blue"
        And the order number "O-001" for 3pl client "Test 3PL Client" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 1 | test-product-blue | 001|
            | #2 | 6" x 6" x 6" Brown Box | 1 | test-product-blue | 001 |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the 3PL "Test 3PL" ships order "O-001" for client "Test 3PL Client" through "FedEx" on the "2023-05-10" with tracking "TN-O-001" the order shipped event is dispatch
        And the shipment of order "O-001" has a label with tracking "TN_O-001" which cost 1.25
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should have 1 invoice items
        And 1 invoice item for 1.38 has a quantity of 1.00 and the description "Shipment Number: TN_O-001 | FedEx via Ground Shipping, order no. O-001"
        And the shipping label rate "Test Shipping Label Rate" billed 1.38 for the "FedEx" shipment on the date "2023-05-10"
