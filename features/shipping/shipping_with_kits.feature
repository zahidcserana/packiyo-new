@shipping @shipping_with_kits
Feature: Shipping kits
    As a packer
    I want to ship only the components of a kit product and have the kit product marked as shipped

    Background:
        Given a customer called "Test Client" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a member user "roger@packiyo.com" named "Roger" based in "United States"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has a shipping box named 'Standard'
        And the customer "Test Client" has a shipping box named 'Standard_1'
        And the user "roger@packiyo.com" belongs to the customer "Test Client"
        And the user "roger@packiyo.com" is authenticated
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95
        And the customer "Test Client" has an SKU "table-top" named "Table Top" priced at 49.99
        And the customer "Test Client" has an SKU "table-leg" named "Table Leg" priced at 19.99
        And the customer "Test Client" has an SKU "table-screw" named "Table Screw" priced at 0.99
        And I manually set 100 of "table-top" into "A-1" location
        And I manually set 200 of "table-leg" into "A-1" location
        And I manually set 1000 of "table-screw" into "A-1" location
        And the product with SKU "table" is a parent kit
        And the SKU "table-top" is added as a component to the kit product with quantity of 1
        And the SKU "table-leg" is added as a component to the kit product with quantity of 4
        And the SKU "table-screw" is added as a component to the kit product with quantity of 50
        And an order with the number "O-001" for 2 SKU "table" is created
        And the customer "Test Client" has an SKU "toolkit-box" named "Toolkit box" priced at 133.95
        And the customer "Test Client" has an SKU "the-best-screwdriver" named "The Best Screwdriver" priced at 19.99
        And the customer "Test Client" has an SKU "the-best-wrench" named "The Best Wrench" priced at 5.99
        And I manually set 20 of "the-best-screwdriver" into "A-1" location
        And I manually set 20 of "the-best-wrench" into "A-1" location

    Scenario: Order including kit is allocated properly
        Then the order "O-001" with line sku "table" should be kit and have 3 component lines
        And the line SKU "table" on the order "O-001" should be allocated and pickable
        And the line SKU "table-top" on the order "O-001" should be allocated and pickable
        And the line SKU "table-leg" on the order "O-001" should be allocated and pickable
        And the line SKU "table-screw" on the order "O-001" should be allocated and pickable

    Scenario: Customer packs components and ships the order. The kit line is set as shipped
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table-top" from "A-1" location
        And I pack 8 of "table-leg" from "A-1" location
        And I pack 100 of "table-screw" from "A-1" location
        And I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 2                           |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 2                           |
        And the line SKU "table-leg" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 8                           |
        And the line SKU "table-screw" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 100                         |

    Scenario: Customer packs components and ships the order in different boxes. The kit line is set as shipped
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 1 of "table-top" from "A-1" location
        And I pack 4 of "table-leg" from "A-1" location
        And I pack 50 of "table-screw" from "A-1" location
        And I take box "Standard_1"
        And I pack 1 of "table-top" from "A-1" location
        And I pack 4 of "table-leg" from "A-1" location
        And I pack 50 of "table-screw" from "A-1" location
        And I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 2                           |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 2                           |
        And the line SKU "table-leg" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 8                           |
        And the line SKU "table-screw" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 100                         |

    Scenario: Customer packs components from one kit. Second kit is unpacked. The kit line is set as partially shipped
        Given I manually set 1 of "table-top" into "A-1" location
        And I manually set 4 of "table-leg" into "A-1" location
        And I manually set 50 of "table-screw" into "A-1" location
        And I start packing order "O-001"
        And the order "O-001" sets allow partial to "on"
        And I take box "Standard"
        And I pack 1 of "table-top" from "A-1" location
        And I pack 4 of "table-leg" from "A-1" location
        And I pack 50 of "table-screw" from "A-1" location
        And I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 1                  | 1                           |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 1                  | 1                           |
        And the line SKU "table-screw" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 50                 | 50                          |

    Scenario: Customer packs one of the components and ships the order. The kit line is not set as shipped
        When I manually set 2 of "table-top" into "A-1" location
        And I manually set 0 of "table-leg" into "A-1" location
        And I manually set 0 of "table-screw" into "A-1" location
        And I start packing order "O-001"
        And the order "O-001" sets allow partial to "on"
        And I take box "Standard"
        And I pack 2 of "table-top" from "A-1" location
        And I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 2                  | 0                           |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 2                           |
        And the line SKU "table-leg" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 8                  | 0                           |

    Scenario: Customer cancels one of the components, packs the other one and ships the order. The kit line is set as shipped
        When the line SKU "table-leg" on the order "O-001" is cancelled
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table-top" from "A-1" location
        And I pack 100 of "table-screw" from "A-1" location
        And I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 2                           |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 2                           |
        And the line SKU "table-leg" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 0                           |
        And the line SKU "table-screw" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 100                         |

    @reship_kits
    Scenario: Customer packs and ship the order with one kit. Then reship the order and again ship the order check columns quantities pending, shipped, and reshipped are match
        # No.1 Packing
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table-top" from "A-1" location
        And I pack 8 of "table-leg" from "A-1" location
        And I pack 100 of "table-screw" from "A-1" location
        # No.1 Ship a kit
        And I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 2                           | 0                      |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 2                           | 0                      |
        And the line SKU "table-leg" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 8                           | 0                      |
        And the line SKU "table-screw" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 100                         | 0                      |
        # No.1 Reship a kit
        When I select "table" to reship 2 from the order "O-001"
        Then I reship the order "O-001"
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 2                  | 2                           | 2                      |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 2                  | 2                           | 2                      |
        And the line SKU "table-leg" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 8                  | 8                           | 8                      |
        And the line SKU "table-screw" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 100                | 100                         | 100                    |
        # No.2 Packing
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table-top" from "A-1" location
        And I pack 8 of "table-leg" from "A-1" location
        And I pack 100 of "table-screw" from "A-1" location
        # No.2 Ship a kit
        And I ship the order using "generic" method
        Then the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 4                           | 2                      |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 4                           | 2                      |
        And the line SKU "table-leg" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 16                          | 8                      |
        And the line SKU "table-screw" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 200                         | 100                    |
        # No.2 Reship a kit
        When I select "table" to reship 2 from the order "O-001"
        Then I reship the order "O-001"
        And the line SKU "table" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 2                  | 4                           | 4                      |
        And the line SKU "table-top" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 2                  | 4                           | 4                      |
        And the line SKU "table-leg" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 8                  | 16                          | 16                     |
        And the line SKU "table-screw" on the order "O-001" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 100                | 200                         | 200                    |

    Scenario: Customer creates a kit with one component.
        After placing an order, the customer attaches additional component on kit but won't update the pending order items.
        After shipping the component the kit is marked as shipped.
        When the product with SKU "toolkit-box" is a parent kit
        And the kit product has the following components attached
            | the-best-screwdriver |
            | 2                    |
        And an order with the number "O-002" for 2 SKU "toolkit-box" is created
        And the line SKU "the-best-screwdriver" on the order "O-002" should be allocated and pickable
        And the kit product has the following components attached
            |the-best-screwdriver  | the-best-wrench      |
            | 4                    | 2                    |
        And the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 2                  | 0                           | 0                      |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 4                  | 0                           | 0                      |
        # Packing
        When I start packing order "O-002"
        And I take box "Standard"
        And I pack 4 of "the-best-screwdriver" from "A-1" location
        # Shipping
        And I ship the order using "generic" method
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 2                           | 0                      |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 4                           | 0                      |

    Scenario: Customer creates a kit one component.
        After placing an order, the customer updates the quantity of the kit's component but won't update the pending order items.
        After shipping the the component with the original quantity the kit is marked as shipped.
        When the product with SKU "toolkit-box" is a parent kit
        And the kit product has the following components attached
            | the-best-screwdriver |
            | 2                    |
        And an order with the number "O-002" for 2 SKU "toolkit-box" is created
        And the line SKU "the-best-screwdriver" on the order "O-002" should be allocated and pickable
        And the kit product has the following components attached
            | the-best-screwdriver |
            | 4                    |
        And the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 2                  | 0                           | 0                      |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 4                  | 0                           | 0                      |
        # Packing
        When I start packing order "O-002"
        And I take box "Standard"
        And I pack 4 of "the-best-screwdriver" from "A-1" location
        # Shipping
        And I ship the order using "generic" method
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 0                  | 2                           | 0                      |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 0                  | 4                           | 0                      | 2                  |

    Scenario: Customer creates a kit with two components.
        After placing an order, the customer updates the quantity of the kit's components but won't update the pending order items.
        After shipping the components with the original quantities the kit is marked as shipped.
        When the product with SKU "toolkit-box" is a parent kit
        And the kit product has the following components attached
            | the-best-screwdriver | the-best-wrench |
            | 4                    | 6               |
        And an order with the number "O-002" for 2 SKU "toolkit-box" is created
        And the line SKU "the-best-screwdriver" on the order "O-002" should be allocated and pickable
        And the line SKU "the-best-wrench" on the order "O-002" should be allocated and pickable
        And the kit product has the following components attached
            | the-best-screwdriver | the-best-wrench |
            | 6                    | 6               |
        And the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 2                  | 0                           | 0                      |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 8                  | 0                           | 0                      |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     |
            | 12                 | 0                           | 0                      |
        # Packing
        When I start packing order "O-002"
        And I take box "Standard"
        And I pack 8 of "the-best-screwdriver" from "A-1" location
        And I pack 12 of "the-best-wrench" from "A-1" location
        # Shipping
        And I ship the order using "generic" method
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 0                  | 2                           | 0                      | null               |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 0                  | 8                           | 0                      | 4                  |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 0                  | 12                          | 0                      | 6                  |

    Scenario: Customer creates a kit with two components.
        After placing an order, the customer updates the quantity of the kit's components and updates the pending order items.
        The components on order are updated with the new quantities
        When the product with SKU "toolkit-box" is a parent kit
        And the kit product has the following components attached
            | the-best-screwdriver | the-best-wrench |
            | 4                    | 6               |
        Then an order with the number "O-002" for 2 SKU "toolkit-box" is created
        And the line SKU "the-best-screwdriver" on the order "O-002" should be allocated and pickable
        And the line SKU "the-best-wrench" on the order "O-002" should be allocated and pickable
        When the kit product has the following components attached
            | the-best-screwdriver | the-best-wrench |
            | 6                    | 6               |
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 2                  | 0                           | 0                      | null               |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 8                  | 0                           | 0                      | 4                  |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 12                 | 0                           | 0                      | 6                  |
        When the kit product is synced with pending order items
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 2                  | 0                           | 0                      | null               |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 12                 | 0                           | 0                      | 6                  |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 12                 | 0                           | 0                      | 6                  |

    Scenario: Customer creates a kit with two components.
        After placing an order with kit and set that order can be shipped partially
        When I manually set 4 of "the-best-screwdriver" into "A-1" location
        And I manually set 6 of "the-best-wrench" into "A-1" location
        And the product with SKU "toolkit-box" is a parent kit
        And the kit product has the following components attached
            | the-best-screwdriver | the-best-wrench |
            | 4                    | 6               |
        Then an order with the number "O-002" for 2 SKU "toolkit-box" is created
        And the order "O-002" sets allow partial to "on"
        When the kit product has the following components attached
            | the-best-screwdriver | the-best-wrench |
            | 6                    | 6               |
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 2                  | 0                           | 0                      | null               |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 8                  | 0                           | 0                      | 4                  |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 12                 | 0                           | 0                      | 6                  |
        # Packing
        When I start packing order "O-002"
        And I take box "Standard"
        And I pack 4 of "the-best-screwdriver" from "A-1" location
        And I pack 6 of "the-best-wrench" from "A-1" location
        # Shipping
        And I ship the order using "generic" method
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 1                  | 1                           | 0                      | null               |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 4                  | 4                           | 0                      | 4                  |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 6                  | 6                           | 0                      | 6                  |

    Scenario: Customer creates a kit with two components.
        After placing an order, the customer updates the quantities of the kit's components and updates the pending order items.
        The order is packed and shipped with the new component quantities.
        When the product with SKU "toolkit-box" is a parent kit
        And the kit product has the following components attached
            | the-best-screwdriver | the-best-wrench |
            | 4                    | 6               |
        Then an order with the number "O-002" for 2 SKU "toolkit-box" is created
        And the order "O-002" sets allow partial to "on"
        When the kit product has the following components attached
            | the-best-screwdriver | the-best-wrench |
            | 6                    | 6               |
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 2                  | 0                           | 0                      | null               |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 8                  | 0                           | 0                      | 4                  |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 12                 | 0                           | 0                      | 6                  |
        And I manually set 6 of "the-best-screwdriver" into "A-1" location
        And I manually set 6 of "the-best-wrench" into "A-1" location
        And the kit product is synced with pending order items
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 2                  | 0                           | 0                      | null               |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 12                 | 0                           | 0                      | 6                  |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 12                 | 0                           | 0                      | 6                  |
        # Packing
        When I start packing order "O-002"
        And I take box "Standard"
        And I pack 6 of "the-best-screwdriver" from "A-1" location
        And I pack 6 of "the-best-wrench" from "A-1" location
        # Shipping
        And I ship the order using "generic" method
        Then the line SKU "toolkit-box" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 1                  | 1                           | 0                      | null               |
        And the line SKU "the-best-screwdriver" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 6                  | 6                           | 0                      | 6                  |
        And the line SKU "the-best-wrench" on the order "O-002" should have the following quantities
            | quantity_pending   | quantity_shipped            | quantity_reshipped     | component_quantity |
            | 6                  | 6                           | 0                      | 6                  |
