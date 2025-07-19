@multiwarehouse @allocate_product_to_warehouse
Feature: Allocate product to warehouse
    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "jonah@packiyo.com" named "Jonah" based in "United States"
        And the user "jonah@packiyo.com" belongs to the customer "Test Client"
        And the user "jonah@packiyo.com" is authenticated
        And a product with SKU "PROD-1" that belongs to customer "Test Client"
        And the warehouse "Primary 1" belongs to customer "Test Client"
        And the warehouse "Primary 2" belongs to customer "Test Client"
        And location "A1" belongs to warehouse "Primary 1"
        And location "A2" belongs to warehouse "Primary 1"
        And location "B1" belongs to warehouse "Primary 2"

    Scenario: Allocate quantity on hand to warehouse
        Given the quantity on hand for product "PROD-1" in location "A1" and in warehouse "Primary 1" is 9
        And the quantity on hand for product "PROD-1" in location "A2" and in warehouse "Primary 1" is 1
        And the quantity on hand for product "PROD-1" in location "B1" and in warehouse "Primary 2" is 90
        And the customer "Test Client" has the feature flag "App\Features\MultiWarehouse" on
        And I allocate the inventory for "PROD-1" in all warehouses for customer "Test Client"
        Then the quantity on hand for "PROD-1" is 100
        And the quantity on hand for product "PROD-1" in warehouse "Primary 1" is 10
