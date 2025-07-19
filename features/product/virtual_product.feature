@product @virtual_product
Feature: Creating and managing virtual products in Packiyo

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "stanley+test_client@packiyo.com" named "Stanley" based in "United States"
        And the user "stanley+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 5 locations of type "A"
        And the customer "Test Client" has an SKU "desk-chair" named "Desk Chair" weighing 15.99
        And the customer "Test Client" has an SKU "rocking-chair" named "Rocking Chair" weighing 18.49
        And the warehouse "Test Warehouse" has 100 SKU "desk-chair" in location "A-0001"
        And the warehouse "Test Warehouse" has 100 SKU "rocking-chair" in location "A-0001"
        And the warehouse "Test Warehouse" has a pickable location called "A-0001"
        And the customer "Test Client" has a shipping box named "Standard"

    Scenario: Creating a product and changing the type of product to Virtual from Regular
        When the user "stanley+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "virtual-chair" named "Virtual Chair" priced at 29.99
        And the user opens product edit form for SKU "virtual-chair"
        And the user sets the product type to virtual
        And the user validates the product update form
        Then validation has "passed"
        When the user submits the product update form
        Then the product type is virtual

    Scenario: An order that has a virtual product as an order line should not try to allocate that product
        When the user "stanley+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "virtual-chair" named "Virtual Chair" priced at 29.99
        And the user opens product edit form for SKU "virtual-chair"
        And the user sets the product type to virtual
        And the user submits the product update form
        And the customer "Test Client" got the order number "O-001" for 5 SKU "desk-chair"
        And the order "O-001" has 100 of the SKU "virtual-chair" added to it
        Then the order "O-001" should have the order line with SKU "virtual-chair"
        And the order line should have 0 items pending
        And the order line should have 100 items shipped
        And the order "O-001" should be ready to ship

    Scenario: An order line that is a virtual product should not be picked/packed, but does have to be added to shipment items
        When the user "stanley+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "virtual-chair" named "Virtual Chair" priced at 29.99
        And the user opens product edit form for SKU "virtual-chair"
        And the user sets the product type to virtual
        And the user submits the product update form
        And the customer "Test Client" got the order number "O-001" for 5 SKU "desk-chair"
        And the order "O-001" has 100 of the SKU "virtual-chair" added to it
        And I recalculate orders that are ready to ship
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 5 of "desk-chair" from "A-0001" location
        And I ship the order using "generic" method
        Then the order "O-001" should have all of its items shipped
        And the order line with SKU "virtual-chair" from order "O-001" has shipment items


