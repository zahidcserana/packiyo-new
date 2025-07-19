@product @non_sellable_inventory
Feature: Include non-sellable quantity by default in available product quantity and when allocating a order check if non-sellable quantity is included by default in allocation process
    As a customer
    I want to have ability to enable or disable non-sellable quantity to be included in allocation when orders have inventory on non-sellable locations

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has a shipping box named 'Standard'
        And the warehouse "Test Warehouse" has a pickable location called "A-0001"
        And the warehouse "Test Warehouse" has a pickable location called "A-0002"
        And the warehouse "Test Warehouse" has a non-sellable location called "A-0003"
        And the customer "Test Client" has an SKU "SKU-1" named "Test Product 1" weighing 4.99
        And the customer "Test Client" has an SKU "SKU-2" named "Test Product 2" weighing 4.99

    Scenario: The product inventory is managed across both sellable and non-sellable locations. Consequently, the non-sellable quantity at the product level will be updated accordingly.
        When the user opens product edit form for SKU "SKU-1"
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0001" with quantity of 400 to the form data
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0003" with quantity of 100 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        And the product SKU "SKU-1" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_available |
            | 500              | 100                   | 400                |

    Scenario: Initially, the product's inventory is located in sellable locations. When transferring quantity to non-sellable locations, ensure that the product-level non-sellable quantity is updated accordingly.
        When the user opens product edit form for SKU "SKU-1"
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0001" with quantity of 500 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        When the product with SKU "SKU-1" is selected
        Then I allocate the SKU "SKU-1"
        And the product SKU "SKU-1" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_available |
            | 500              | 0                     | 500                |
        Then I transfer 10 of "SKU-1" from "A-0001" location into "A-0003" location
        And the product should have inventory 490 on location "A-0001"
        And the product should have inventory 10 on location "A-0003"
        And the product SKU "SKU-1" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_available |
            | 500              | 10                    | 490                |

    Scenario: The product inventory includes both sellable and non-sellable quantities. The feature flag is enabled by default, any order allocations will not include non-sellable quantities. As a result, the order status will be marked as ready to ship.
        When the user opens product edit form for SKU "SKU-1"
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0001" with quantity of 10 to the form data
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0003" with quantity of 10 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        When the customer "Test Client" got the order number "ORD-1" for 20 SKU "SKU-1"
        And I manually set 10 of "SKU-2" into "A-0001" location
        And the order "ORD-1" has 5 of the SKU "SKU-2" added to it
        And I allocate the SKU "SKU-1"
        And I allocate the SKU "SKU-2"
        Then I recalculate orders that are ready to ship
        And the product SKU "SKU-1" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_allocated | quantity_available |
            | 20               | 10                    | 20                 | 0                  |
        And the product SKU "SKU-2" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_allocated | quantity_available |
            | 10               | 0                     | 5                  | 5                  |
        And the line SKU "SKU-1" on the order "ORD-1" should have the following quantities
            | quantity_pending | quantity_allocated            | quantity_backordered |
            | 20               | 20                            | 0                    |
        And the line SKU "SKU-2" on the order "ORD-1" should have the following quantities
            | quantity_pending   | quantity_allocated            | quantity_backordered |
            | 5                  | 5                             | 0                    |

    Scenario: The product inventory includes both sellable and non-sellable quantities. When the feature flag 'AllowNonSellableAllocation' is disabled, any order allocations will include non-sellable quantities. As a result, the order status will be marked as backordered instead of ready, since the inventory is not fully available for fulfillment.
        When the instance has the feature flag "App\Features\AllowNonSellableAllocation" disable
        And the user opens product edit form for SKU "SKU-1"
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0001" with quantity of 10 to the form data
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0003" with quantity of 10 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        When the customer "Test Client" got the order number "ORD-1" for 20 SKU "SKU-1"
        And I manually set 10 of "SKU-2" into "A-0001" location
        And the order "ORD-1" has 5 of the SKU "SKU-2" added to it
        And I allocate the SKU "SKU-1"
        And I allocate the SKU "SKU-2"
        Then I recalculate orders that are ready to ship
        And the product SKU "SKU-1" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_allocated | quantity_available |
            | 20               | 10                    | 10                 | 0                  |
        And the product SKU "SKU-2" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_allocated | quantity_available |
            | 10               | 0                     | 5                  | 5                  |
        And the line SKU "SKU-1" on the order "ORD-1" should have the following quantities
            | quantity_pending | quantity_allocated            | quantity_backordered |
            | 20               | 10                            | 10                   |
        And the line SKU "SKU-2" on the order "ORD-1" should have the following quantities
            | quantity_pending   | quantity_allocated            | quantity_backordered |
            | 5                  | 5                             | 0                    |

    Scenario: The product inventory includes both sellable and non-sellable quantities, with the feature flag enabled by default. The non-sellable quantity at the product level will be updated accordingly. When packing and shipping an order, the non-sellable quantity is included in the allocation, ensuring the order is marked as ready to ship.
        When the user opens product edit form for SKU "SKU-1"
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0001" with quantity of 10 to the form data
        And the user from warehouse "Test Warehouse" adds inventory in location "A-0003" with quantity of 10 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        When the customer "Test Client" got the order number "ORD-1" for 20 SKU "SKU-1"
        And I manually set 10 of "SKU-2" into "A-0002" location
        And the order "ORD-1" has 5 of the SKU "SKU-2" added to it
        And I allocate the SKU "SKU-1"
        And I allocate the SKU "SKU-2"
        Then I recalculate orders that are ready to ship
        And the product SKU "SKU-1" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_allocated | quantity_available |
            | 20                | 10                   | 20                 | 0                  |
        And the product SKU "SKU-2" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_allocated | quantity_available |
            | 10               | 0                     | 5                  | 5                  |
        When I start packing order "ORD-1"
        And I take box "Standard"
        And I pack 10 of "SKU-1" from "A-0001" location
        And I pack 10 of "SKU-1" from "A-0003" location
        And I pack 5 of "SKU-2" from "A-0002" location
        Then I ship the order using "generic" method
        And the product SKU "SKU-1" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_allocated | quantity_available |
            | 0                | 0                     | 0                  | 0                  |
        And the product SKU "SKU-2" should have the following quantities
            | quantity_on_hand | quantity_non_sellable | quantity_allocated | quantity_available |
            | 5                | 0                     | 0                  | 5                  |
        And the line SKU "SKU-1" on the order "ORD-1" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 20                          |
        And the line SKU "SKU-2" on the order "ORD-1" should have the following quantities
            | quantity_pending   | quantity_shipped            |
            | 0                  | 5                           |
