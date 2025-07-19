@inventory @locations
Feature: Calculating the occupied locations for 3PL clients
    As the owner of a 3PL business
    I want to be able to tell which locations were occupied by specific clients
    So that I can charge my customers for those locations.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49

    Scenario: Calculating occupied locations with an inventory log on the date
        Given the customer "Test Client" has an SKU "recursive-kit-cyan" named "Recursive Kit Cyan" priced at 3.99
        And the SKU "recursive-kit-cyan" is added as a component to the kit product with quantity of 1
        When I allocate the SKU "recursive-kit-cyan"
        Then the app should have logged a "warning" with the following message
            """
            Refusing to allocate a recursive kit, SKU recursive-kit-cyan.
            """
