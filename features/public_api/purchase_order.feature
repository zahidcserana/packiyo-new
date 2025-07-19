@public_api @public_api_purchase_order
Feature: Import purchase orders
    As a merchant
    I want to create or update purchase orders with public API

Background:
    # Standalone customer
    Given a customer called "Test Client" based in "United States"
    And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
    And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
    And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
    And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" priced at 49.99
    And the warehouse "Test Warehouse" had quantity 30 that attached in product with location "A-1"
    And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 39.99
    And the warehouse "Test Warehouse" had quantity 20 that attached in product with location "A-1"
    And the customer "Test Client" has a supplier "Supplier 1"
    And the user has an API access token named "Roger - Public API" with the ability "public-api"
    And I call the "/api/v1/users/me" endpoint

    Scenario: the customer store purchase order with items and tags using public API
        When I pass in request body data attributes
            | number          | PO-001                   |
            | warehouse_name  | Test Warehouse           |
            | notes           | Test note text           |
            | tracking_number | 123456789                |
            | tracking_url    | https://tracking.url/123 |
            | notes           | Test note text           |
            | priority        | 1                        |
            | tags            | testTag1, testTag2       |
        And I pass in request body a single relationship resource "purchase_order_items_data"
            | sku                 | test-product-red  |
            | quantity            | 10                |
        And I pass in request body a single relationship resource "purchase_order_items_data"
            | sku                 | test-product-blue |
            | quantity            | 5                 |
        And I store the purchase order using public API
        Then the response contains the field "data.attributes.number" with the value "PO-001"
        And the response contains the field "data.relationships.purchase_order_items.data" with data count 2
        And the response contains the field "data.relationships.purchase_order_items.data.0.type" with the value "purchase-order-items"
        And the response code is "201"
        And the purchase order "PO-001" should have these tags
            | testTag1 | testTag2 |

    Scenario: update purchase order with items and tags using public API
        When the client "Test Client" has a pending purchase order "PO-001" for 10 of the SKU "test-product-blue"
        And I pass in request body data attributes
            | number          | PO-001                   |
            | warehouse_name  | Test Warehouse           |
            | notes           | Test note text           |
            | tracking_number | 123456789                |
            | tracking_url    | https://tracking.url/123 |
            | notes           | Test note text           |
            | priority        | 1                        |
            | tags            | testTag1, testTag2       |
        And I pass in request body a single relationship resource "purchase_order_items_data"
            | sku                 | test-product-blue |
            | quantity            | 5                 |
            | quantity_received   | 0                 |
            | quantity_sell_ahead | 10                |
        And I update the purchase order using public API
        Then the response contains the field "data.relationships.purchase_order_items.data" with data count 1
        And the response contains the field "data.attributes.number" with the value "PO-001"
        And the response contains the field "data.relationships.purchase_order_items.data.0.type" with the value "purchase-order-items"
        And the response code is "200"
        And the purchase order "PO-001" should have these tags
            | testTag1 | testTag2 |
        And the purchase order "PO-001" item "test-product-blue" should contains in field "quantity" a value "5"

    Scenario: update purchase order's quantity and quantity received where quantity is less than quantity received
        When the client "Test Client" has a pending purchase order "PO-001" for 10 of the SKU "test-product-blue"
        And I pass in request body data attributes
            | number          | PO-001                   |
            | warehouse_name  | Test Warehouse           |
            | notes           | Test note text           |
            | tracking_number | 123456789                |
            | tracking_url    | https://tracking.url/123 |
            | notes           | Test note text           |
            | priority        | 1                        |
            | tags            | testTag1, testTag2       |
        And I pass in request body a single relationship resource "purchase_order_items_data"
            | sku                 | test-product-blue |
            | quantity            | 6                 |
            | quantity_received   | 7                 |
        And I update the purchase order using public API
        Then the response error with field "errors.0.detail" contains error message
            """
            The purchase_order_items.0.quantity must be greater than or equal 7.
            """
        Then the response code is "422"

    Scenario: update purchase order's quantity where quantity is less then original received quantity
        When the client "Test Client" has a pending purchase order "PO-001" for 100 of the SKU "test-product-blue"
        And the SKU "test-product-blue" on the purchase order "PO-001" has quantity received of 200
        And I pass in request body data attributes
            | number          | PO-001                   |
            | warehouse_name  | Test Warehouse           |
            | notes           | Test note text           |
            | tracking_number | 123456789                |
            | tracking_url    | https://tracking.url/123 |
            | notes           | Test note text           |
            | priority        | 1                        |
            | tags            | testTag1, testTag2       |
        And I pass in request body a single relationship resource "purchase_order_items_data"
            | sku                 | test-product-blue |
            | quantity_received   | 200               |
            | quantity            | 150               |
        And I update the purchase order using public API
        Then the response error with field "errors.0.detail" contains error message
            """
            The purchase_order_items.0.quantity must be greater than or equal 200.
            """
        Then the response code is "422"

    Scenario: update purchase order's sell ahead with original quantity less then original received quantity
        When the client "Test Client" has a pending purchase order "PO-001" for 100 of the SKU "test-product-blue"
        And the SKU "test-product-blue" on the purchase order "PO-001" has quantity received of 200
        And I pass in request body data attributes
            | number          | PO-001                   |
            | warehouse_name  | Test Warehouse           |
            | notes           | Test note text           |
            | tracking_number | 123456789                |
            | tracking_url    | https://tracking.url/123 |
            | notes           | Test note text           |
            | priority        | 1                        |
            | tags            | testTag1, testTag2       |
        And I pass in request body a single relationship resource "purchase_order_items_data"
            | sku                 | test-product-blue |
            | quantity            | 100                |
            | quantity_received   | 200                |
            | quantity_sell_ahead | 50                |
        And I update the purchase order using public API
        Then the response code is "200"
        And the purchase order "PO-001" item "test-product-blue" should contains in field "quantity" a value "100"
        And the purchase order "PO-001" item "test-product-blue" should contains in field "quantity_sell_ahead" a value "50"

    Scenario: update purchase order's sell ahead and quantity where quantity is equal to original received quantity
        When the client "Test Client" has a pending purchase order "PO-001" for 100 of the SKU "test-product-blue"
        And the SKU "test-product-blue" on the purchase order "PO-001" has quantity received of 200
        And I pass in request body data attributes
            | number          | PO-001                   |
            | warehouse_name  | Test Warehouse           |
            | notes           | Test note text           |
            | tracking_number | 123456789                |
            | tracking_url    | https://tracking.url/123 |
            | notes           | Test note text           |
            | priority        | 1                        |
            | tags            | testTag1, testTag2       |
        And I pass in request body a single relationship resource "purchase_order_items_data"
            | sku                 | test-product-blue |
            | quantity            | 200                |
            | quantity_received   | 200                |
            | quantity_sell_ahead | 50                |
        And I update the purchase order using public API
        Then the response code is "200"
        And the purchase order "PO-001" item "test-product-blue" should contains in field "quantity" a value "200"
        And the purchase order "PO-001" item "test-product-blue" should contains in field "quantity_sell_ahead" a value "50"

    # TODO add other scenarios that assert 200

    Scenario: the customer store purchase order using public API without warehouse name and validation will fail
        When I pass in request body data attributes
            | number          | PO-001                   |
            | supplier_name   | Test Client              |
            | notes           | Test note text           |
            | tracking_number | 123456789                |
            | tracking_url    | https://tracking.url/123 |
            | notes           | Test note text           |
            | priority        | 1                        |
        And I pass in request body a single relationship resource "purchase_order_items_data"
            | sku                 | test-product-red  |
            | quantity            | 10                |
        And I store the purchase order using public API
        Then the response error with field "errors.0.detail" contains error message
            """
            The warehouse id field is required.
            """
        And the response code is "422"

    Scenario: the customer store purchase order using public API without purchase order items and validation will fail
        When I pass in request body data attributes
            | number          | PO-001                   |
            | warehouse_name  | Test Warehouse           |
            | supplier_name   | Test Client              |
            | notes           | Test note text           |
            | tracking_number | 123456789                |
            | tracking_url    | https://tracking.url/123 |
            | notes           | Test note text           |
            | priority        | 1                        |
        And I store the purchase order using public API
        Then the response error with field "errors.0.detail" contains error message
            """
            The purchase order items field is required.
            """
        And the response code is "422"
