@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
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
        # 3PL and 3PL client
        And a 3PL called "Test 3PL" based in "United States"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a sales channel named "punkrock.shopify.com"
        And the customer "Test 3PL Client" has an SKU "test-product-green" named "Test Product Green" weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-orange" named "Test Product Orange" weighing 8.49
        And a customer called "Another 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Another 3PL Client" has a sales channel named "heavymetal.shopify.com"

    Scenario: Listing automations, having no automations
        When I call the "/api/frontendv1/automations?include=applies_to_customers,actions,conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data" with an empty list
        And the response does not contain the field "included"

    Scenario: Listing a standalone client's automations, having one automation
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is "US"
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        When I call the "/api/frontendv1/automations?include=applies_to_customers,actions,conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "order-automations"
        And the response contains the text field "data.0.id"
        And the response contains the number field "data.0.attributes.position" with the value 1
        And the response contains the field "data.0.attributes.target_events" with the values
            | ordercreatedevent |
            | orderupdatedevent |
        And the response contains the text field "data.0.attributes.created_at"
        And the response contains the text field "data.0.attributes.updated_at"
        And the response contains the field "data.0.relationships.applies_to_customers.data" with an empty list
        And the response contains the field "included.0.type" with the value "ship-to-country-conditions"
        And the response contains the field "included.0.attributes.title" with the value "Ship-to country"
        And the response contains the Boolean field "included.0.attributes.case_sensitive" with the value "true"
        And the response contains the number field "included.0.attributes.position" with the value 1
        And the response contains the field "included.0.attributes.comparison_operator" with the value "some_equals"
        And the response contains the field "included.0.attributes.countries" with the values
            | US |
        And the response contains the field "included.1.type" with the value "set-shipping-method-actions"
        And the response contains the object field "included.1.relationships.shipping_method"

    Scenario: Listing a 3PL client's automations, having one automation
        Given an order automation named "Set shipping method" owned by "Test 3PL" is enabled
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test 3PL"
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation is triggered when the ship to country is "US"
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        When I call the "/api/frontendv1/automations?include=applies_to_customers,actions,conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "order-automations"
        And the response contains the text field "data.0.id"
        And the response contains the number field "data.0.attributes.position" with the value 1
        And the response contains the field "data.0.attributes.target_events" with the values
            | ordercreatedevent |
            | orderupdatedevent |
        And the response contains the text field "data.0.attributes.created_at"
        And the response contains the text field "data.0.attributes.updated_at"
        # And the response contains the field "data.0.relationships.applies_to_customers.data" with an empty list
        And the response contains the field "data.0.relationships.applies_to_customers.data" with a reference to the customer "Test 3PL Client"
        And the response contains the field "included.0.type" with the value "customers"
        And the response contains the field "included.1.type" with the value "ship-to-country-conditions"
        And the response contains the Boolean field "included.1.attributes.case_sensitive" with the value "true"
        And the response contains the number field "included.1.attributes.position" with the value 1
        And the response contains the field "included.1.attributes.comparison_operator" with the value "some_equals"
        And the response contains the field "included.1.attributes.title" with the value "Ship-to country"
        And the response contains the field "included.1.attributes.countries" with the values
            | US |
        And the response contains the field "included.2.type" with the value "set-shipping-method-actions"
        And the response contains the object field "included.2.relationships.shipping_method"
