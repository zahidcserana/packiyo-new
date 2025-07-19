@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want to automate order management actions on creation for Standalone clients for specific sources
    So that I can ensure every time an order is created or updated by a Standalone client is not tag.

    Background:
        Given a customer called "Test Client" based in "United States"
        And the customer "Test Client" has the feature flag "App\Features\CoPilot" on
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And the customer "Test Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" and barcoded "119657000081"
        And the product has a weight of 1.5
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" and barcoded "119657000082"
        And the product has a weight of 2.5
        And the customer "Test Client" has an SKU "test-product-green" named "Test Product Green" weighing 0.50
        And the customer "Test Client" has an SKU "test-kit-green" named "Test Kit Green" weighing 10.00
        And the SKU "test-product-green" is added as a component to the kit product with quantity of 20
        And the customer "Test Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 1.50
        And the customer "Test Client" has an SKU "test-kit-purple" named "Test Kit Purple" weighing 30.00
        And the SKU "test-product-purple" is added as a component to the kit product with quantity of 20

    Scenario: When order is created by a Standalone and by Form source, and the automation is set to trigger when created by Form source,
    then automation should not tag order
        Given an order automation named "Tag order for standalone client" owned by "Test Client" is enabled
        And a customer called "Test Client 2" based in "United States" client of 3PL "Test Client"
        And the customer "Test Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to 3pl
        And the automation is triggered when and order is created by "FORM" type
        And the automation is triggered when and order is created by a 3pl
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_client@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created by source "FORM"
        #Then the order "O-001" should not have these tags
        #    | SP-Edit |Co-Pilot|
