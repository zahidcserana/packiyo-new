@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want to automate order management actions on creation
    So that I can ensure those actions are accurately and efficiently performed.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49
        And the customer "Test Client" has an SKU "test-product-cyan" named "Test Product Cyan" priced at 3.99
        And the customer "Test Client" has an SKU "test-product-magenta" named "Test Product Magenta" priced at 5.99
        And the customer "Test Client" has an SKU "test-product-black" named "Test Product Black" priced at 8.49

    Scenario: Ensuring automation actions run in order
        Given an order automation named "Add two packing notes" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Handle with care."
        And the automation adds the packing note "Pack with love."
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-yellow"
        Then the order "O-001" should have the following packing note
            """
            Handle with care.
            - Co-Pilot
            ————————————
            Pack with love.
            - Co-Pilot
            """
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add two packing notes: Packing note set to "Handle with care.
            - Co-Pilot"
            """
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add two packing notes: Packing note changed from "Handle with care.
            - Co-Pilot" to "Handle with care.
            - Co-Pilot
            ————————————
            Pack with love.
            - Co-Pilot"
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Ensuring automations run in order
        Given an order automation named "Add a packing note" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Handle with care."
        Given an order automation named "Add another packing note" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Pack with love."
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-yellow"
        Then the order "O-001" should have the following packing note
            """
            Handle with care.
            - Co-Pilot
            ————————————
            Pack with love.
            - Co-Pilot
            """

    Scenario: Ensuring automations run in order after moving one up
        Given an order automation named "Add a packing note" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Handle with care."
        Given an order automation named "Add another packing note" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Pack with love."
        Given an order automation named "Add yet another packing note" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Treat with kindness."
        And the automation "Add another packing note" is moved to position number 1
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-yellow"
        Then the order "O-001" should have the following packing note
            """
            Pack with love.
            - Co-Pilot
            ————————————
            Handle with care.
            - Co-Pilot
            ————————————
            Treat with kindness.
            - Co-Pilot
            """

    Scenario: Ensuring automations run in order after moving one down
        Given an order automation named "Add a packing note" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Handle with care."
        Given an order automation named "Add another packing note" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Pack with love."
        Given an order automation named "Add yet another packing note" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds the packing note "Treat with kindness."
        And the automation "Add another packing note" is moved to position number 3
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-yellow"
        Then the order "O-001" should have the following packing note
            """
            Handle with care.
            - Co-Pilot
            ————————————
            Treat with kindness.
            - Co-Pilot
            ————————————
            Pack with love.
            - Co-Pilot
            """
