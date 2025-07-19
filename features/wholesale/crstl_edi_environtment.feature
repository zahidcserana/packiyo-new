@wholesale @orders
Feature: Using different environments to communicate with Crstl API
    As a merchant
    I want to be able to use different environments to communicate with Crstl API
    So that I can test the integration without affecting the production environment

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

    Scenario: Requesting login information for Crstl API in sandbox environment
        Given Crstl production base url is "https://api.crstl.so"
        And Crstl sandbox base url is "https://sandbox-api.crstl.so"
        And the customer "Test Client" gets a set of mocked API tokens using their Sandbox Crstl credentials
        Then the latest Crstl API call should've been made to "https://sandbox-api.crstl.so"

    Scenario: Requesting login information for Crstl API in production environment
        Given Crstl production base url is "https://api.crstl.so"
        And Crstl sandbox base url is "https://sandbox-api.crstl.so"
        And the customer "Test Client" gets a set of mocked API tokens using their Crstl credentials
        Then the latest Crstl API call should've been made to "https://api.crstl.so"

    Scenario: Submitting the ASN information using the Crstl API in sandbox environment
        Given Crstl production base url is "https://api.crstl.so"
        And Crstl sandbox base url is "https://sandbox-api.crstl.so"
        And the customer "Test Client" gets a set of mocked API tokens using their Sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the packed order "PASB010357-7331" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB010357-7331" ASN information to a mocked endpoint
        Then the latest Crstl API call should've been made to "https://sandbox-api.crstl.so"

    Scenario: Submitting the ASN information using the Crstl API in production environment
        Given Crstl production base url is "https://api.crstl.so"
        And Crstl sandbox base url is "https://sandbox-api.crstl.so"
        And the customer "Test Client" gets a set of mocked API tokens using their Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB010357-7331" with external ID "65e18293af23790d0d5abcb0" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB010357-7331" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the packed order "PASB010357-7331" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB010357-7331" ASN information to a mocked endpoint
        Then the latest Crstl API call should've been made to "https://api.crstl.so"
