@automation @orders @public_api
Feature: Run an order automation on creation
    As a warehouse manager
    I want my automations to run on orders created using the Public API
    So that I can ensure they go through the same workflows as other orders.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And the customer "Test Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49
        # TODO: Getting 'Undefined constant "App\Http\Middleware\LARAVEL_START"' without this step.
        And I call the "/api/v1/users/me" endpoint

    Scenario: Running automations on an order created using the Public API
        Given an order automation named "Add tag because of product" owned by "Test Client" is enabled
        And the automation is triggered when the order has at least 6 items
        And the automation is triggered when the order has at most 8 items
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        When the customer "Test Client" gets the order number "O-001" from the Public API for these SKUs
            | 3 | test-product-blue |
            | 3 | test-product-red  |
        Then the response code is "201"
        And the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add tag because of product: Added "contains-yellow, probably-duckling" tags
            """
