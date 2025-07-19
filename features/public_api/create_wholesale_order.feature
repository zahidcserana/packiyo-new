@public_api @orders @wholesale
Feature: Create wholesale order
    As a merchant shipping to retailers
    I want third parties to be able to create wholesale orders
    So that I can fulfill my retailer contracts efficiently and correctly.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And I call the "/api/v1/users/me" endpoint

    Scenario: Creating a wholesale order matching products with barcodes
        Given the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" and barcoded "product-blue-barcode"
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" and barcoded "product-red-barcode"
        When the customer "Test Client" gets the wholesale order number "O-001" from the Public API for these barcodes
            | 3 | product-blue-barcode |
            | 3 | product-red-barcode  |
        Then the response code is "201"
        And the order "O-001" is flagged as wholesale
        And the order "O-001" should have a line item with 3 of the SKU "test-product-blue"
        And the order "O-001" should have a line item with 3 of the SKU "test-product-red"
