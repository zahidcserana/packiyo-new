@public_api @users @auth @brand_user_token
Feature: Authenticating on the Public API
    As a third party developer
    I want to be able to identify myself as brand when interacting with the Packiyo Public API
    So that I can access and manage data private to my profile.
    Apart from my resources, I want to see only resources from my account

    Background:
        # First standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has a supplier "Supplier 1"
        And the customer "Test Client" has an SKU "client-1-sku-1" named "client-1-product-1" sized "10" x "10" x "10"
        And the customer "Test Client" gets the order number "client-order-001" for these SKUs
            | 1 | client-1-sku-1 |
        And the client "Test Client" has a pending purchase order "client-po-001" for "1" of the SKU "client-1-sku-1"
        # Second standalone customer
        Given a customer called "Test Client 2" based in "United States"
        And a member user "joe+test_client@packiyo.com" named "Joe" based in "United States"
        And the user "joe+test_client@packiyo.com" belongs to the customer "Test Client 2"
        And the customer "Test Client 2" has a warehouse named "Test Second Warehouse" in "United States"
        And the customer "Test Client 2" has a supplier "Supplier 2"
        And the customer "Test Client 2" has an SKU "client-2-sku-1" named "client-2-product-1" sized "10" x "10" x "10"
        And the customer "Test Client 2" has an SKU "client-2-sku-2" named "client-2-product-2" sized "10" x "10" x "10"
        And the customer "Test Client 2" gets the order number "client-2-order-001" for these SKUs
            | 1 | client-2-sku-1 |
            | 2 | client-2-sku-2 |
        And the customer "Test Client 2" gets the order number "client-2-order-002" for these SKUs
            | 1 | client-2-sku-1 |


    # Products
    Scenario: through the public API, first brand customer can only see products associated with their brand.
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test Client"
        Then I call the "/api/v1/users/me" endpoint
        And I call the "/api/v1/products" endpoint
        And the response contains the field "data.0.type" with the value "products"
        And the response contains the number field "meta.page.total" with the value "1"
        And the response code is 200

    Scenario: through the public API, second brand customer can only see products associated with their brand.
        When I will work with user "joe+test_client@packiyo.com"
        And I will work with customer "Test Client 2"
        And the user has an API access token named "Joe - Public API" with the ability "public-api" and assigned to customer "Test Client 2"
        Then I call the "/api/v1/users/me" endpoint
        And I call the "/api/v1/products" endpoint
        And the response contains the field "data.0.type" with the value "products"
        And the response contains the number field "meta.page.total" with the value "2"
        And the response code is 200

    @public_api_filters
    Scenario: through the public API, first brand customer can filter products from their brands.
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test Client"
        Then I call the "/api/v1/users/me" endpoint
        And I call the "/api/v1/products" endpoint to filter child customer "Test Client" objects
        And the response contains the field "data.0.type" with the value "products"
        And the response contains the number field "meta.page.total" with the value "1"
        And the response code is 200

    @public_api_filters
    Scenario: through the public API, first brand customer cannot filter products from other brands.
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test Client"
        Then I call the "/api/v1/users/me" endpoint
        And I call the "/api/v1/products" endpoint to filter child customer "Test Client 2" objects
        And the response contains the field "data" with an empty list
        And the response code is 200

    # Orders
    Scenario: through the public API, first brand customer can only see orders associated with their brand.
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test Client"
        Then I call the "/api/v1/users/me" endpoint
        And I call the "/api/v1/orders" endpoint
        And the response contains the field "data.0.type" with the value "orders"
        And the response contains the number field "meta.page.total" with the value "1"
        And the response code is 200

    Scenario: through the public API, second brand customer can only see orders associated with their brand.
        When I will work with user "joe+test_client@packiyo.com"
        And I will work with customer "Test Client 2"
        And the user has an API access token named "Joe - Public API" with the ability "public-api" and assigned to customer "Test Client 2"
        Then I call the "/api/v1/users/me" endpoint
        And I call the "/api/v1/orders" endpoint
        And the response contains the field "data.0.type" with the value "orders"
        And the response contains the number field "meta.page.total" with the value "2"
        And the response code is 200

    # Purchase orders
    Scenario: through the public API, first brand customer can only see purchase orders associated with their brand.
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test Client"
        Then I call the "/api/v1/users/me" endpoint
        And I call the "/api/v1/purchase-orders" endpoint
        And the response contains the field "data.0.type" with the value "purchase-orders"
        And the response contains the number field "meta.page.total" with the value "1"
        And the response code is 200

    Scenario: through the public API, second brand customer doesn't have purchase orders so data will be empty list
        When I will work with user "joe+test_client@packiyo.com"
        And I will work with customer "Test Client 2"
        And the user has an API access token named "Joe - Public API" with the ability "public-api" and assigned to customer "Test Client 2"
        Then I call the "/api/v1/users/me" endpoint
        And I call the "/api/v1/purchase-orders" endpoint
        And the response contains the field "data" with an empty list
        And the response code is 200
