@public_api @public_api_order @orders
Feature: Import orders with unknown products
    As a merchant
    I want orders to be imported regardless of whether their products have already been imported
    So that when the products are later imported the orders can be shipped.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And I call the "/api/v1/users/me" endpoint
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99

    Scenario: Running order created using the Public API
        Given the customer "Test Client" deletes product with SKU "test-product-red"
        When the customer "Test Client" gets the order number "O-001" from the Public API for these SKUs
            | 3 | test-product-red  |
        Then the response code is "201"

    Scenario: Store order with non-existing product using Public Api
        When the customer "Test Client" gets the order number "order-number-100" from the Public API without product ID with SKU "sku-123" and quantity "5"
        Then the response code is "201"
        And the response contains the field "data.relationships.order_items.data" with data count "1"
        And the response contains the field "data.relationships.order_items.data.0.type" with the value "order-items"
        And the response code is "201"
        And the order "order-number-100" should have these tags
            | orderTag1 | orderTag2 |

    Scenario: Store order with non-existing product using Public Api and then product was created then order item need to have same details as product
        Given the customer "Test Client" gets the order number "order-number-100" from the Public API without product ID with SKU "sku-123" and quantity 5
        And the response code is "201"
        And the order "order-number-100" should have empty product ID in order item SKU "sku-123"
        When the customer "Test Client" has a product named "New Test Product" with SKU "sku-123" weighing 20.00 and sized 10.50 x 5.00 x 6.00
        Then the order "order-number-100" should have the product in order item SKU "sku-123" with same details as product

    Scenario: Store order with non-existing product using Public Api and then other product sku was changed then order item detail and quantity allocation are changed
        Given the customer "Test Client" has a product named "New Test Product" with SKU "sku-123" weighing 20.00 and sized 10.50 x 5.00 x 6.00
        And the warehouse "Test Warehouse" had quantity 20 that attached in product with location "loc"
        When the customer "Test Client" gets the order number "order-number-100" from the Public API without product ID with SKU "sku-1234" and quantity 5
        Then the response code is "201"
        And the order "order-number-100" should have empty product ID in order item SKU "sku-1234"
        When the customer "Test Client" with product named "New Test Product" has changed SKU to value "sku-1234"
        Then the order "order-number-100" should have the product in order item SKU "sku-1234" with same details as product
        And the line SKU "sku-1234" on the order "order-number-100" should be allocated and pickable
