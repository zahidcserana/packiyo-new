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
        # Test Kit Blue
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 0.50
        And the customer "Test Client" has an SKU "test-kit-blue" named "Test Kit Blue" and barcoded "0119657000081"
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 10
        # Test Kit Red
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 1.50
        And the customer "Test Client" has an SKU "test-kit-red" named "Test Kit Red" and barcoded "0119657000082"
        And the SKU "test-product-red" is added as a component to the kit product with quantity of 10
        # Test Kit Green
        And the customer "Test Client" has an SKU "test-product-green" named "Test Product Green" weighing 0.50
        And the customer "Test Client" has an SKU "test-kit-green" named "Test Kit Green" and barcoded "85000816019"
        And the SKU "test-product-green" is added as a component to the kit product with quantity of 12
        # Test Kit Purple
        And the customer "Test Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 1.50
        And the customer "Test Client" has an SKU "test-kit-purple" named "Test Kit Purple" and barcoded "85000816006"
        And the SKU "test-product-purple" is added as a component to the kit product with quantity of 12

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB43570 (kits to cases)
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB43570" with external ID "65fd72ffdea7d7562bb29d0b" for these items
            |  1 | test-kit-blue | 0119657000081 |
            | 36 | test-kit-red  | 0119657000082 |
        And the order number "PASB43570" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 10 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 60 | test-product-red  |
            | #3 | 6" x 6" x 6" Brown Box | 60 | test-product-red  |
            | #4 | 6" x 6" x 6" Brown Box | 60 | test-product-red  |
            | #5 | 6" x 6" x 6" Brown Box | 60 | test-product-red  |
            | #6 | 6" x 6" x 6" Brown Box | 60 | test-product-red  |
            | #7 | 6" x 6" x 6" Brown Box | 60 | test-product-red  |
        And the packed order "PASB43570" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB43570" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 0 items
        # TODO: Shouldn't there be a label per package? (Six packages, six labels.)
        And the order "PASB43570" should have 0 GS1-128 labels queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PA1-3736815 (kits to cases)
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PA1-3736815" with external ID "662f4fc86f11a6fede955d21" for these items
            | 12 | test-kit-green  | 85000816019 |
            | 12 | test-kit-purple | 85000816006 |
        And the order number "PA1-3736815" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 12 | test-product-green  |
            | #2 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #3 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #4 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #5 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #6 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #7 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
        And the packed order "PA1-3736815" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PA1-3736815" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 0 items
        # TODO: Shouldn't there be a label per package? (Six packages, six labels.)
        And the order "PA1-3736815" should have 0 GS1-128 labels queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PA2-3736815 (kits to cases)
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PA2-3736815" with external ID "662f52586f11a6fede955d28" for these items
            | 12 | test-kit-green  | 85000816019 |
            | 12 | test-kit-purple | 85000816006 |
        And the order number "PA2-3736815" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 12 | test-product-green  |
            | #2 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #3 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #4 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #5 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #6 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #7 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
        And the packed order "PA2-3736815" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PA2-3736815" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 0 items
        # TODO: Shouldn't there be a label per package? (Six packages, six labels.)
        And the order "PA2-3736815" should have 0 GS1-128 labels queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PA3-3336815 (kits to cases)
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PA3-3336815" with external ID "6646f975ca6933bed4a07273" for these items
            | 12 | test-kit-green  | 85000816019 |
            | 12 | test-kit-purple | 85000816006 |
        And the order number "PA3-3336815" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 12 | test-product-green  |
            | #2 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #3 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #4 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #5 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #6 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
            | #7 | 6" x 6" x 6" Brown Box | 12 | test-product-purple |
        And the packed order "PA3-3336815" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PA3-3336815" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 0 items
        # TODO: Shouldn't there be a label per package? (Six packages, six labels.)
        And the order "PA3-3336815" should have 0 GS1-128 labels queued for printing
