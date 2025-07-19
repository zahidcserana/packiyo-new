@wholesale @orders
Feature: Download GS1-128 labels for wholesale orders
    As a merchant
    I want to download GS1-128 labels for wholesale orders
    So that I can print them and attach them to the boxes

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
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" and barcoded "119657000082"
        And the customer "Test Client" has an SKU "test-product-green" named "Test Product Green" weighing 0.50
        And the customer "Test Client" has an SKU "test-kit-green" named "Test Kit Green" weighing 10.00
        And the SKU "test-product-green" is added as a component to the kit product with quantity of 20
        And the customer "Test Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 1.50
        And the customer "Test Client" has an SKU "test-kit-purple" named "Test Kit Purple" weighing 30.00
        And the SKU "test-product-purple" is added as a component to the kit product with quantity of 20

    Scenario: Download GS1-128 labels for a wholesale order
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the user "roger+test_client@packiyo.com" is authenticated
        And the packed order "PASB010357-7331" is shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the web app checks if the GS1-128 labels are available until they are
        When the web app requests to see the first GS1-128 label
        Then the web app should show the GS1-128 label

    Scenario: Download for GS1-128 labels fails
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the user "roger+test_client@packiyo.com" is authenticated
        And the packed order "PASB010357-7331" is shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the labels' content aren't available for download
        When the web app checks if the GS1-128 labels are available until they are
        Then it should try to download the file 2 times
        And it should log the error after these tries

    Scenario: Download GS1-128 labels for a wholesale order without content
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the user "roger+test_client@packiyo.com" is authenticated
        And the packed order "PASB010357-7331" is shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the web app checks if the GS1-128 labels are available until they are
        And the first EDI label has no content
        When the web app requests to see the first GS1-128 label
        Then the web app should show the GS1-128 label information as json
