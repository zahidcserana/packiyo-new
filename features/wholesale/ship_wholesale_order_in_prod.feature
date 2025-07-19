@wholesale @orders
Feature: Ship a wholesale order via freight
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
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" and barcoded "119657000082"

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PACK-112T
        Given the customer "Test Client" gets a set of API tokens using their production Crstl credentials
        And the customer "Test Client" got the wholesale order "PACK-112T" with external ID "6655496d7e83f4f8599e2f64" for these items
            | 8 | test-product-blue | 869657000081 |
            | 8 | test-product-red  | 869657000082 |
        And the order number "PACK-112T" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 8 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 8 | test-product-red  |
        And the packed order "PACK-112T" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PACK-112T" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PACK-112T" should have 1 GS1-128 label queued for printing

    @skip-ci
    Scenario: Printing the GS1-128 labels for the Crstl EDI order PACK-T101911
        Given the customer "Test Client" gets a set of API tokens using their production Crstl credentials
        And the customer "Test Client" got the wholesale order "PACK-T101911" with external ID "66568f6e7e83f4f8599e334f" for these items
            | 90 | test-product-blue | TSETTFM1001 |
            | 30 | test-product-red  | TSDPG1003   |
        And the order number "PACK-T101911" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 30 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 30 | test-product-blue |
            | #3 | 6" x 6" x 6" Brown Box | 30 | test-product-blue |
            | #4 | 6" x 6" x 6" Brown Box | 20 | test-product-red  |
        And the packed order "PACK-T101911" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PACK-T101911" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 2 item
        And the order "PACK-T101911" should have 2 GS1-128 label queued for printing
