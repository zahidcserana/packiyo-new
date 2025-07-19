# adding @skip-ci until we switch calling real postman API with VCR.
@shipping @shipping_with_external_carrier @skip-ci
Feature: Shipping using external carrier credentials
    As a packer
    I want to ship using external carrier shipping method and return success shipment with labels and tracking links

    Background:
        Given a customer called "Test Client" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a member user "roger@packiyo.com" named "Roger" based in "United States"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has a shipping box named 'Standard'
        And the user "roger@packiyo.com" belongs to the customer "Test Client"
        And the user "roger@packiyo.com" is authenticated
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95 and based in "United States"
        And I manually set 10 of "table" into "A-1" location
        And an order with the number "O-001" for 2 SKU "table" is created
        And the customer has created external carrier credentials
            | create_shipment_label_url                                            | create_return_label_url                                            | void_label_url                                                  | get_carriers_url                                                        | reference |
            | https://c6faeac9-9cbe-4542-ae74-d43c09e9cfd6.mock.pstmn.io/shipments | https://c6faeac9-9cbe-4542-ae74-d43c09e9cfd6.mock.pstmn.io/returns | https://c6faeac9-9cbe-4542-ae74-d43c09e9cfd6.mock.pstmn.io/void | https://c6faeac9-9cbe-4542-ae74-d43c09e9cfd6.mock.pstmn.io/get_carriers | ref-01    |

    Scenario: Customer packs and ships the order. Ship the order using external shipping method and should have success shipment with labels and tracking links
        When I start packing order "O-001"
        Then I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        And I expect to have 1 shipping carriers with 2 shipping methods
        And I ship the order using "External Method 1" method
        Then I expect the order shipment to be successful, with a line containing SKU "table" and a shipped quantity of 2.0
        And I expect the order shipment labels to include tracking links

    Scenario: Customer packs and ships the order using external carrier shipping method and then customer returns shipped order lines using same shipping method and should have success shipment with labels and tracking links
        When I start packing order "O-001"
        Then I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        And I expect to have 1 shipping carriers with 2 shipping methods
        And I ship the order using "External Method 1" method
        Then I expect the order shipment to be successful, with a line containing SKU "table" and a shipped quantity of 2.0
        And I expect the order shipment labels to include tracking links
        Then I return order "O-001" and choose order line SKU "table" from location "A-1" with quantity 2
        And I return the order using "External Method 1" method
        Then I expect the order return to be successful, with a line containing SKU "table" and a returned quantity of 2
        And I expect the order return label to include tracking links


    Scenario: Customer packs and ships the order. Ship the order using external shipping method and should have success shipment with labels and tracking links and then customer try to void shipment and shipment should have voided status
        When I start packing order "O-001"
        Then I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        And I expect to have 1 shipping carriers with 2 shipping methods
        And I ship the order using "External Method 1" method
        Then I expect the order shipment to be successful, with a line containing SKU "table" and a shipped quantity of 2.0
        And I expect the order shipment labels to include tracking links
        Then I void the order shipment labels
            """
            Shipment successfully voided.
            """
        And I expect the order shipment is voided
