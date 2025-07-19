@public_api @public_api_product @products
Feature: Import products
    As a merchant
    I want to create or update products with tags in public API

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has a supplier "Supplier 1"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And I call the "/api/v1/users/me" endpoint

    Scenario: the customer store product with tags using public API
        When I pass in request body data attributes
            | sku                   | test-product-blue  |
            | name                  | Test product blue  |
            | price                 | 59.99              |
            | width                 | 13                 |
            | height                | 10                 |
            | length                | 11                 |
            | weight                | 12                 |
            | barcode               | BAR-01             |
            | notes                 | Test note text     |
            | value                 | 15                 |
            | customs_price         | 20                 |
            | customs_description   | test product       |
            | hs_code               | HS1111             |
            | country_of_origin     | US                 |
            | tags                  | testTag1, testTag2 |
        And I store the product using public API
        Then the response contains the field "data.attributes.sku" with the value "test-product-blue"
        And the response code is "201"
        And the product "test-product-blue" should have these tags
            | testTag1 | testTag2 |

    Scenario: the customer updates product with tags using public API
        When the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 39.99
        And I pass in request body data attributes
            | sku                   | test-product-blue-01  |
            | name                  | Test product blue 01  |
            | price                 | 59.99                 |
            | width                 | 20                    |
            | tags                  | testTag1, testTag2    |
        And I update the product using public API
        Then the response contains the field "data.attributes.sku" with the value "test-product-blue-01"
        And the response contains the field "data.attributes.name" with the value "Test product blue 01"
        And the response contains the field "data.attributes.price" with the value "59.99"
        And the response contains the number field "data.attributes.width" with the value 20
        And the response code is "200"
        And the product "test-product-blue-01" should have these tags
            | testTag1 | testTag2 |

    Scenario: the customer store product using public API without sku name and validation will fail
        When I pass in request body data attributes
            | name                  | Test product blue 01  |
            | price                 | 59.99                 |
            | width                 | 20                    |
            | tags                  | testTag1, testTag2    |
        And I store the product using public API
        Then the response error with field "errors.0.detail" contains error message
            """
            The sku field is required.
            """
        And the response code is "422"

