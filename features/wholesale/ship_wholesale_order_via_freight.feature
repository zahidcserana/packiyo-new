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
        # And the customer "Test Client" has an SKU "test-product-green" named "Test Product Green" weighing 0.50
        # And the customer "Test Client" has an SKU "test-kit-green" named "Test Kit Green" and barcoded "85000816019"
        # And the SKU "test-product-green" is added as a component to the kit product with quantity of 20
        # And the customer "Test Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 1.50
        # And the customer "Test Client" has an SKU "test-kit-purple" named "Test Kit Purple" and barcoded "85000816006"
        # And the SKU "test-product-purple" is added as a component to the kit product with quantity of 20

    # Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB000001-0001
    #     Given the customer "Test Client" has a Crstl account
    #     And the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
    #     And the customer "Test Client" got the wholesale order "PASB000001-0001" with external ID "64faedde2ccc0a6717497e05" for these items
    #         # | 288 | VN_01_MT12 | 119657000081 |
    #         # | 288 | VN_01_MG12 | 119657000082 |
    #         # TODO: Crstl is mapping both with our own db ID and with the SKU. Problem?
    #         | 288 | test-product-blue | 546 |
    #         | 288 | test-product-red  | 547 |
    #     And the order number "PASB000001-0001" was packed as follows
    #         # | #1 | 6" x 6" x 6" Brown Box | 288 | VN_01_MG12 |
    #         # | #2 | 6" x 6" x 6" Brown Box | 288 | VN_01_MT12 |
    #         | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
    #         | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
    #     And the packed order "PASB000001-0001" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
    #     And the user "roger+test_client@packiyo.com" is authenticated
    #     When the packer submits the ASN information
    #     And the packer prints the GS1-128 labels
    #     Then the printing queue should have 1 item
    #     And the order "PASB000001-0001" should have 1 GS1-128 label queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB000001-0002
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB000001-0002" with external ID "6532f197f2a76a689260cc82" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB000001-0002" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the packed order "PASB000001-0002" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB000001-0002" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PASB000001-0002" should have 1 GS1-128 label queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB000001-0003
        Given the customer "Test Client" has a Crstl account
        And the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB000001-0003" with external ID "658160444202048c6b454dec" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB000001-0003" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the packed order "PASB000001-0003" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB000001-0003" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PASB000001-0003" should have 1 GS1-128 label queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB000001-0004
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB000001-0004" with external ID "658160444202048c6b454ded" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB000001-0004" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the packed order "PASB000001-0004" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB000001-0004" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PASB000001-0004" should have 1 GS1-128 label queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB000001-0005
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB000001-0005" with external ID "65816ecb4202048c6b454df3" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB000001-0005" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the packed order "PASB000001-0005" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB000001-0005" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PASB000001-0005" should have 1 GS1-128 label queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB000001-0006
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB000001-0006" with external ID "65816ecb4202048c6b454df7" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB000001-0006" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the packed order "PASB000001-0006" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB000001-0006" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PASB000001-0006" should have 1 GS1-128 label queued for printing

    # Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB000001-0007
    #     # Given the customer "Test Client" has a Crstl account
    #     Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
    #     And the customer "Test Client" got the wholesale order "PASB000001-0007" with external ID "65816ecb4202048c6b454df4" for these items
    #         # | 288 | VN_01_MT12 | 119657000081 |
    #         # | 288 | VN_01_MG12 | 119657000082 |
    #         # TODO: Crstl is mapping both with our own db ID and with the SKU. Problem?
    #         | 288 | test-product-blue | 546 |
    #         # | 288 | test-product-red  | 547 |
    #     # And the order number "PASB000001-0005" is required to be packed as follows
    #     #     | 288 | test-product-blue | 546 |
    #     #     | 288 | test-product-red  | 547 |
    #     And the order number "PASB000001-0007" was packed as follows
    #         # | #1 | 6" x 6" x 6" Brown Box | 288 | VN_01_MG12 |
    #         # | #2 | 6" x 6" x 6" Brown Box | 288 | VN_01_MT12 |
    #         | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
    #         # | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
    #     And the packed order "PASB000001-0007" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
    #     And the user "roger+test_client@packiyo.com" is authenticated
    #     When the packer submits the ASN information
    #     And the packer prints the GS1-128 labels
    #     Then the printing queue should have 1 item
    #     And the order "PASB000001-0007" should have 1 GS1-128 label queued for printing

    Scenario: Printing the GS1-128 labels for the Crstl EDI order PASB000001-0007 (again)
        # Given the customer "Test Client" has a Crstl account
        Given the customer "Test Client" gets a set of API tokens using their sandbox Crstl credentials
        And the customer "Test Client" got the wholesale order "PASB000001-0007" with external ID "65816ecb4202048c6b454df6" for these items
            | 288 | test-product-blue | 119657000081 |
            | 288 | test-product-red  | 119657000082 |
        And the order number "PASB000001-0007" was packed as follows
            | #1 | 6" x 6" x 6" Brown Box | 288 | test-product-blue |
            | #2 | 6" x 6" x 6" Brown Box | 288 | test-product-red  |
        And the packed order "PASB000001-0007" was shipped from the "Test Warehouse" warehouse through "Generic" on the "2023-11-27"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the packer submits the order "PASB000001-0007" ASN information
        And the packer prints the GS1-128 labels
        Then the printing queue should have 1 item
        And the order "PASB000001-0007" should have 1 GS1-128 label queued for printing
