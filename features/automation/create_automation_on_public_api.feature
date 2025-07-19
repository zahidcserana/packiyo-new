@automation @orders
Feature: Run an order automation on creation
    As an integrating
    I want my automations to run on orders created using the Public API
    So that I can ensure they go through the same workflows as other orders.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And the customer "Test Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49

    Scenario: Listing all automation types
        When I call the "/api/frontendv1/automatable-operations?include=supported_events" endpoint
        Then the response code is "200"
        And the response contains the field "data.0.attributes.type" with the value "App\Models\Order"
        And the response contains the field "data.0.relationships.supported_events.data.0.id" with the value "ordercreatedevent"
        And the response contains the field "data.0.relationships.supported_events.data.1.id" with the value "orderupdatedevent"
        And the response contains the field "data.0.relationships.supported_events.data.2.id" with the value "orderagedevent"
        And the response contains the field "data.0.relationships.supported_events.data.3.id" with the value "ordershippedevent"
        And the response contains the field "data.1.attributes.type" with the value "App\Models\PurchaseOrder"
        And the response contains the field "data.1.relationships.supported_events.data.0.id" with the value "purchaseordercreatedevent"
        And the response contains the field "data.1.relationships.supported_events.data.1.id" with the value "purchaseorderreceivedevent"
        And the response contains the field "data.1.relationships.supported_events.data.2.id" with the value "purchaseorderclosedevent"
        And the response contains the field "included.0.attributes.name" with the value "OrderCreatedEvent"
        And the response contains the field "included.0.attributes.title" with the value "Order Created"
        And the response contains the field "included.1.attributes.name" with the value "OrderUpdatedEvent"
        And the response contains the field "included.1.attributes.title" with the value "Order Updated"
        And the response contains the field "included.2.attributes.name" with the value "OrderAgedEvent"
        And the response contains the field "included.2.attributes.title" with the value "Order Aged"
        And the response contains the field "included.3.attributes.name" with the value "OrderShippedEvent"
        And the response contains the field "included.3.attributes.title" with the value "Order Shipped"
        And the response contains the field "included.4.attributes.name" with the value "PurchaseOrderCreatedEvent"
        And the response contains the field "included.4.attributes.title" with the value "Purchase Order Created"
        And the response contains the field "included.5.attributes.name" with the value "PurchaseOrderReceivedEvent"
        And the response contains the field "included.5.attributes.title" with the value "Purchase Order Received"
        And the response contains the field "included.6.attributes.name" with the value "PurchaseOrderClosedEvent"
        And the response contains the field "included.6.attributes.title" with the value "Purchase Order Closed"

    Scenario: Create an automation for the owning customer with a single action
        Given the placeholder "customerId" is the ID of the customer "Test Client"
        When I post this to the "/api/frontendv1/order-automations" endpoint
            """
            {
                "data": {
                    "type": "order-automations",
                    "attributes": {
                        "name": "Set shipping method",
                        "is_enabled": true,
                        "target_events": ["ordercreatedevent", "orderupdatedevent"]
                    },
                    "relationships": {
                        "customer": {
                            "data": {
                                "type": "customers",
                                "id": ":customerId:"
                            }
                        }
                    }
                }
            }
            """
        Then the response code is "201"
        And the response contains the field "data.type" with the value "order-automations"
        And the response contains the text field "data.id"
        And the response contains the field "data.attributes.name" with the value "Set shipping method"
        And the response contains the Boolean field "data.attributes.is_enabled" with the value "false"
        Given the placeholder "automationId" is the ID of the automation "Set shipping method"
        When I post this to the "/api/frontendv1/order-text-field-conditions" endpoint
            """
            {
                "data": {
                    "type": "order-text-field-conditions",
                    "attributes": {
                        "case_sensitive": true,
                        "comparison_operator": "some_equals",
                        "field_name": "shippingContactInformation.country.iso_3166_2",
                        "text_field_values": ["US"],
                        "position": 1
                    },
                    "relationships": {
                        "automation": {
                            "data": {
                                "type": "automations",
                                "id": ":automationId:"
                            }
                        }
                    }
                }
            }
            """
        Then the response code is "201"
