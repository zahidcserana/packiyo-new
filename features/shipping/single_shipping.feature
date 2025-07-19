@shipping @single_shipping
Feature: Single Shipping
    As a user
    I want to be able to ship an order
    So that I can send the order to the customer

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
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95
        And the customer "Test Client" has an SKU "door" named "Door" priced at 150.00
        And the customer "Test Client" has an SKU "tv" named "TV" priced at 250.00
        And I manually set 100 of "table" into "A-1" location
        And I manually set 100 of "door" into "A-1" location

    Scenario: Order without allow partial shipment and customer packs half the items and tries to ship
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        When I try to ship the order using "generic" method
        Then The shipment should fail with message "Some items were reallocated. Please refresh and pack again."

    Scenario: Order with allow partial shipment and customer packs half the items and tries to ship
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" sets allow partial to "on"
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        When I try to ship the order using "generic" method
        Then The shipment should fail with message "Some items were reallocated. Please refresh and pack again."

    Scenario: Shipping an order with virtual product and without allow partial
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And the customer "Test Client" has an SKU "test-product-virtual" named "Test Product Virtual" weighing 5.99
        And the product's type is set to virtual
        And the order "O-001" has 2 of the SKU "test-product-virtual" added to it
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        And I pack 2 of "door" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 0                  | 2                           | 0                  |
        And the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 0                  | 2                           | 0                  |
        And the line SKU "test-product-virtual" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 0                  | 2                           | 0                  |

    Scenario: Shipping a order with a cancelled order item and without allow partial
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And the line SKU "door" on the order "O-001" is cancelled
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 0                  | 2                           | 0                  |

    Scenario: Order with allow partial shipment set to off and one line item has no quantity allocated
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" sets allow partial to "off"
        # tv has no inventory allocated
        And the order "O-001" has 1 of the SKU "tv" added to it
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I pack 2 of "table" from "A-1" location
        When I try to ship the order using "generic" method
        Then The shipment should fail with message "Order is not ready to ship."

    Scenario: Order with allow partial shipment and one line item has no quantity allocated
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" sets allow partial to "on"
        # tv has no inventory allocated
        And the order "O-001" has 1 of the SKU "tv" added to it
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I pack 2 of "table" from "A-1" location
        When I try to ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 0                  | 2                           | 0                  |
        And the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 0                  | 2                           | 0                  |
        And the line SKU "tv" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 1                  | 0                           | 0                  |

    Scenario: Customer ships an order with allow partial in two different shipments, one with all items and one with partial
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" sets allow partial to "on"
        And I manually set 0 of "table" into "A-1" location
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 2                  | 0                           | 0                  |
        And the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 0                  | 2                           | 0                  |
        And I manually set 1 of "table" into "A-1" location
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 1 of "table" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 1                  | 1                           | 0                  |
        And the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated |
            | 0                  | 2                           | 0                  |

    Scenario: Order with allow partial, reship one line item twice and don't ship one
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" has 2 of the SKU "tv" added to it
        And the order "O-001" sets allow partial to "on"
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I pack 2 of "table" from "A-1" location
        # Shipment 1
        When I ship the order using "generic" method
        Then the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 2                           | 0                  | 0                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 2                           | 0                  | 0                  |
        # Re-ship 1
        When I select "door" to reship 2 from the order "O-001"
        Then I reship the order "O-001"
        And the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 2                  | 2                           | 2                  | 2                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 2                           | 0                  | 0                  |
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 4                           | 0                  | 2                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 2                           | 0                  | 0                  |
        # Re-ship 2
        When I select "door" to reship 2 from the order "O-001"
        Then I reship the order "O-001"
        And the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 2                  | 4                           | 2                  | 4                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 2                           | 0                  | 0                  |
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 6                           | 0                  | 4                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 2                           | 0                  | 0                  |

    Scenario: Order without allow partial, reship full order
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" has 2 of the SKU "door" added to it
        And the order "O-001" sets allow partial to "off"
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I pack 2 of "table" from "A-1" location
        # Shipment 1
        When I ship the order using "generic" method
        Then the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 2                           | 0                  | 0                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 2                           | 0                  | 0                  |
        # Re-ship 1
        When I select "door" to reship 2 from the order "O-001"
        And I select "table" to reship 2 from the order "O-001"
        Then I reship the order "O-001"
        And the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 2                  | 2                           | 2                  | 2                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 2                  | 2                           | 2                  | 2                  |
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I pack 2 of "table" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 4                           | 0                  | 2                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 4                           | 0                  | 2                  |
        # Re-ship 2
        When I select "door" to reship 2 from the order "O-001"
        And I select "table" to reship 2 from the order "O-001"
        Then I reship the order "O-001"
        And the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 2                  | 4                           | 2                  | 4                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 2                  | 4                           | 2                  | 4                  |
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "door" from "A-1" location
        And I pack 2 of "table" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "door" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 6                           | 0                  | 4                  |
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_reshipped |
            | 0                  | 6                           | 0                  | 4                  |

    Scenario: Order with allow partial, partially ship 1 out of 2 tables, backorder 1 (without inventory)
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" sets allow partial to "on"
        And I manually set 1 of "table" into "A-1" location
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 1 of "table" from "A-1" location
        When I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_allocated | quantity_backordered |
            | 1                  | 1                           | 0                  | 1                    |

    Scenario: Order without allow partial, partially ship 1 out of 2 tables, backorder 1 (without inventory)
        Given an order with the number "O-001" for 2 SKU "table" is created
        And the order "O-001" sets allow partial to "off"
        And I manually set 1 of "table" into "A-1" location
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 1 of "table" from "A-1" location
        When I try to ship the order using "generic" method
        Then The shipment should fail with message "Order is not ready to ship."

