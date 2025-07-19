@public_api @users @auth @3pl_user_token
Feature: Authenticating on the Public API
    As a third party developer
    I want to be able to identify myself as 3pl when interacting with the public API
    So that I can access my and my client data

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test 3PL"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3pl" has a supplier "Supplier 1"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And a customer called "Test 3PL Second Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL" has an SKU "sku-1" named "product-1" sized "10" x "10" x "10"
        # First child client
        And the customer "Test 3PL Client" has an SKU "sku-child-1" named "product-child-1" sized "10" x "10" x "10"
        And the customer "Test 3PL Client" has an SKU "sku-child-2" named "product-child-2" sized "10" x "10" x "10"
        And the customer "Test 3PL Client" gets the order number "child-order-001" for these SKUs
            | 1 | sku-child-1 |
            | 2 | sku-child-2 |
        And the client "Test 3PL Client" has a pending purchase order "child-po-001" for "1" of the SKU "sku-child-1"
        # Second child client
        And the customer "Test 3PL Second Client" has an SKU "sku-second-child-1" named "second-child-product-1" sized "10" x "10" x "10"
        And the customer "Test 3PL Second Client" has an SKU "sku-second-child-2" named "second-child-product-2" sized "10" x "10" x "10"
        And the customer "Test 3PL Second Client" gets the order number "second-child-order-001" for these SKUs
            | 1 | sku-second-child-1 |
        And the customer "Test 3PL Second Client" gets the order number "second-child-order-002" for these SKUs
            | 1 | sku-second-child-1 |
        And the client "Test 3PL Second Client" has a pending purchase order "second-child-po-001" for "1" of the SKU "sku-second-child-1"

    # Product
    Scenario: through the public API, 3pl customer can see products associated with all of theirs clients
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test 3PL"
        Then I call the "/api/v1/products" endpoint
        And the response contains the field "data.0.type" with the value "products"
        And the response contains the number field "meta.page.total" with the value "5"
        And the response code is 200

    Scenario: through the public API, 3pl child customer can only see products that are related to this customer
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL Client"
        And the user has an API access token named "3PL Client - Public API" with the ability "public-api" and assigned to customer "Test 3PL Client"
        Then I call the "/api/v1/users/me" endpoint
        Then I call the "/api/v1/products" endpoint
        And the response contains the field "data.0.type" with the value "products"
        And the response contains the number field "meta.page.total" with the value "2"
        And the response code is 200

    # Filter products
    @filter_public_api
    Scenario: through the public API, 3pl customer filter selected child customer and check if products are visible in list
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test 3PL"
        Then I call the "/api/v1/users/me" endpoint
        Then I call the "/api/v1/products" endpoint to filter child customer "Test 3PL Second Client" objects
        And the response contains the field "data.0.type" with the value "products"
        And the response contains the number field "meta.page.total" with the value "2"
        And the response code is 200

    # Order
    Scenario: through the public API, 3pl customer can see orders associated with all of theirs clients
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test 3PL"
        Then I call the "/api/v1/users/me" endpoint
        Then I call the "/api/v1/orders" endpoint
        And the response contains the field "data.0.type" with the value "orders"
        And the response contains the number field "meta.page.total" with the value "3"
        And the response code is 200

    Scenario: through the public API, 3pl child customer can see orders that are related to this customer
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL Client"
        And the user has an API access token named "3PL Client - Public API" with the ability "public-api" and assigned to customer "Test 3PL Client"
        Then I call the "/api/v1/users/me" endpoint
        Then I call the "/api/v1/orders" endpoint
        And the response contains the field "data.0.type" with the value "orders"
        And the response contains the number field "meta.page.total" with the value "1"
        And the response code is 200

    # Filter orders
    @filter_public_api
    Scenario: through the public API, 3pl customer filter selected child customer and check if orders are visible in list
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test 3PL"
        Then I call the "/api/v1/users/me" endpoint
        Then I call the "/api/v1/orders" endpoint to filter child customer "Test 3PL Second Client" objects
        And the response contains the field "data.0.type" with the value "orders"
        And the response contains the number field "meta.page.total" with the value "2"
        And the response code is 200

    @filter_public_api
    Scenario: through the public API, 3pl child customer filter selected child customer and check if orders are not visible in list
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL Client"
        And the user has an API access token named "3PL Client - Public API" with the ability "public-api" and assigned to customer "Test 3PL Client"
        Then I call the "/api/v1/users/me" endpoint
        Then I call the "/api/v1/orders" endpoint to filter child customer "Test 3PL Second Client" objects
        And the response contains the field "data" with an empty list
        And the response code is 200

    # Purchase orders
    Scenario: through the public API, 3pl customer can see purchase orders associated with all of theirs clients
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test 3PL"
        Then I call the "/api/v1/users/me" endpoint
        Then I call the "/api/v1/purchase-orders" endpoint
        And the response contains the field "data.0.type" with the value "purchase-orders"
        And the response contains the number field "meta.page.total" with the value "2"
        And the response code is 200

    # Filter purchase orders
    @filter_public_api
    Scenario: through the public API, 3pl customer filter selected child customer and check if orders are visible in list
        When I will work with user "roger+test_client@packiyo.com"
        And I will work with customer "Test 3PL"
        And the user has an API access token named "Roger - Public API" with the ability "public-api" and assigned to customer "Test 3PL"
        Then I call the "/api/v1/users/me" endpoint
        Then I call the "/api/v1/purchase-orders" endpoint to filter child customer "Test 3PL Second Client" objects
        And the response contains the field "data.0.type" with the value "purchase-orders"
        And the response contains the number field "meta.page.total" with the value "1"
        And the response code is 200
