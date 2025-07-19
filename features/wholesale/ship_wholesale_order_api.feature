@wholesale @orders
Feature: Ship a wholesale order using the internal API
    As a merchant
    I want to be able to ship B2B wholesale orders to retailers as their provider
    So that I can expand the reach and visibility of my products.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And the customer "Test Client" has the feature flag "App\Features\WholesaleEDI" on
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

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB010357-7331
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1             | 6" x 6" x 6" Brown Box | 288      | test-product-blue | 1.5    |
            | #2             | 6" x 6" x 6" Brown Box | 288      | test-product-red  | 2.5    |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packed order "PASB010357-7331" is shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the web app checks if the GS1-128 labels are available until they are
        And the web app prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PASB010357-7331" should have 1 GS1-128 label queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB010357-7331 2
        Given the customer "Test Client" has a Crstl account
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1             | 6" x 6" x 6" Brown Box | 288      | test-product-blue | 432    |
            | #2             | 6" x 6" x 6" Brown Box | 288      | test-product-red  | 720    |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packed order "PASB010357-7331" is shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the web app checks if the GS1-128 labels are available until they are
        And the web app prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PASB010357-7331" should have 1 GS1-128 label queued for printing

    Scenario: Ensure packages weight are correct
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" has the setting "weight_unit" set to "oz"
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1             | 6" x 6" x 6" Brown Box | 288      | test-product-blue | 432    |
            | #2             | 6" x 6" x 6" Brown Box | 288      | test-product-red  | 720    |
        And the user "roger+test_client@packiyo.com" is authenticated
        When we mock that the packed order "PASB010357-7331" is shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And we store the response's body
        Then the request's packages information should be
            | Package Number | Weight | Weight Unit |
            | #1             | 432    | OZ          |
            | #2             | 720    | OZ          |
        And the request's measurements weight should be 1152
        And the request's measurements weight unit should be "OZ"

    Scenario: Ensure packages count is correct
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1             | 6" x 6" x 6" Brown Box | 288      | test-product-blue | 432    |
            | #2             | 6" x 6" x 6" Brown Box | 288      | test-product-red  | 720    |
        And the user "roger+test_client@packiyo.com" is authenticated
        When we mock that the packed order "PASB010357-7331" is shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And we store the response's body
        Then the request's packages count should be 2
