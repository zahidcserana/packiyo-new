@multiwarehouse
Feature: Transfer orders
    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "jonah@packiyo.com" named "Jonah" based in "United States"
        And the user "jonah@packiyo.com" belongs to the customer "Test Client"
        And the user "jonah@packiyo.com" is authenticated
        And the warehouse "Sender" belongs to customer "Test Client"
        And the warehouse "Receiving" belongs to customer "Test Client"
        And location "A1" belongs to warehouse "Sender"
        And location "B1" belongs to warehouse "Receiving"
        And a supplier that belong to customer "Test Client"

    Scenario: Create a transfer order to move inventory between warehouses
        Given a product with SKU "PROD-1" that belongs to customer "Test Client"
        And the quantity on hand for product "PROD-1" in location "A1" and in warehouse "Sender" is 10
        And a transfer order with number "TR-ORD" assigned to the warehouse "Receiving" with 5 SKU "PROD-1"
        Then I will have a purchase order with number "TR-ORD" that belongs to warehouse "Receiving"
        And the shipping address for order "TR-ORD" is the same as the address from warehouse "Receiving"

    Scenario: Create a transfer order to move inventory in the same warehouse
        Given a product with SKU "PROD-2" that belongs to customer "Test Client"
        And the quantity on hand for product "PROD-2" in location "B1" and in warehouse "Receiving" is 10
        Then a transfer order with number "TR-ORD" assigned to the same warehouse "Receiving" with 5 SKU "PROD-2" will fail
