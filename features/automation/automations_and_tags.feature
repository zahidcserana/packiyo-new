@automation @orders @tags
Feature: Trigger automations by tag, set tags on operation
    As a warehouse manager
    I want to run automations by tags and use them to set tags
    So that I can use the flexibity afforded by tags on my workflows.

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

    Scenario: Trigger by all order tags to add tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has these tags
            | b2b | wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b | wholesale | credit-card |
        Then the order "O-001" should have these tags
            | b2b | wholesale | b2b-pipeline | requires-forklift | credit-card | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by all order tags to add tags with different cases
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has these tags
            | B2b | Wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b | wholesale | credit-card |
        Then the order "O-001" should have these tags
            | b2b | wholesale | credit-card | b2b-pipeline | requires-forklift | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not trigger by all order tags to add tags with different cases
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has these tags
            | B2b | Wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b | credit-card |
        Then the order "O-001" should have these tags
            | b2b | credit-card |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by some order tags with different cases
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has some of these tags
            | B2b | Wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b |
        Then the order "O-001" should have these tags
            | Co-Pilot | b2b | b2b-pipeline | requires-forklift |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not trigger by some order tags with different cases
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has some of these tags
            | B2b | Wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | credit-card |
        Then the order "O-001" should have these tags
            | credit-card |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by none order tags with different cases
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has none of these tags
            | B2b | Wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | credit-card |
        Then the order "O-001" should have these tags
            | credit-card | Co-Pilot | b2b-pipeline | requires-forklift |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not trigger by none order tags with different cases
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has none of these tags
            | B2B | Wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b |
        Then the order "O-001" should have these tags
            | b2b |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by some products having tags to add tags with different cases
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order has a line item with these tags
            | Dark-Jedi | Lightsaber |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the SKU "test-product-red" of client "Test Client" is tagged as "dark-Jedi"
        And the SKU "test-product-red" of client "Test Client" is tagged as "lightSaber"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 1 | test-product-blue   |
            | 3 | test-product-yellow |
        Then the order "O-001" should have these tags
            | b2b-pipeline | requires-forklift | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by all products not having any the tags with different cases
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when none of the line items have these tags
            | Dark-Jedi | Lightsaber |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the SKU "test-product-red" of client "Test Client" is tagged as "a"
        And the SKU "test-product-red" of client "Test Client" is tagged as "b"
        And the SKU "test-product-blue" of client "Test Client" is tagged as "A"
        And the SKU "test-product-blue" of client "Test Client" is tagged as "B"
        And the SKU "test-product-yellow" of client "Test Client" is tagged as "a"
        And the SKU "test-product-yellow" of client "Test Client" is tagged as "B"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 1 | test-product-blue   |
            | 3 | test-product-yellow |
        Then the order "O-001" should have these tags
            | b2b-pipeline | requires-forklift | Co-Pilot |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not triggering by not all order tags present
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has these tags
            | b2b | wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2c | wholesale | credit-card |
        Then the order "O-001" should have these tags
            | b2c | wholesale | credit-card |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by some order tags to add tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has some of these tags
            | retailer | wholesale |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b | wholesale | credit-card |
        Then the order "O-001" should have these tags
            | b2b | wholesale | b2b-pipeline | requires-forklift | credit-card | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not triggering by none order tags present
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has some of these tags
            | b2c | retailer |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b | wholesale | credit-card |
        Then the order "O-001" should have these tags
            | b2b | wholesale | credit-card |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by no order tags to add tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has none of these tags
            | b2c | dropship |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b | wholesale | credit-card |
        Then the order "O-001" should have these tags
            | b2b | wholesale | b2b-pipeline | requires-forklift | credit-card | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not triggering by any of a group of order tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has none of these tags
            | b2c | dropship |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2c | wholesale | credit-card |
        Then the order "O-001" should have these tags
            | b2c | wholesale | credit-card |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not triggering by none of a group of order tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has none of these tags
            | b2c | dropship |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2c | dropship | credit-card |
        Then the order "O-001" should have these tags
            | b2c | dropship | credit-card |
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by some products having tags to add tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order has a line item with these tags
            | dark-jedi | lightsaber |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the SKU "test-product-red" of client "Test Client" is tagged as "dark-jedi"
        And the SKU "test-product-red" of client "Test Client" is tagged as "lightsaber"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 1 | test-product-blue   |
            | 3 | test-product-yellow |
        Then the order "O-001" should have these tags
            | b2b-pipeline | requires-forklift | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by all products having tags to add tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when all line items have these tags
            | dark-jedi | lightsaber |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the SKU "test-product-red" of client "Test Client" is tagged as "dark-jedi"
        And the SKU "test-product-red" of client "Test Client" is tagged as "lightsaber"
        And the SKU "test-product-blue" of client "Test Client" is tagged as "dark-jedi"
        And the SKU "test-product-blue" of client "Test Client" is tagged as "lightsaber"
        And the SKU "test-product-yellow" of client "Test Client" is tagged as "dark-jedi"
        And the SKU "test-product-yellow" of client "Test Client" is tagged as "lightsaber"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 1 | test-product-blue   |
            | 3 | test-product-yellow |
        Then the order "O-001" should have these tags
            | b2b-pipeline | requires-forklift | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not triggering by not all products having tags to add tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when all line items have these tags
            | dark-jedi | lightsaber |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the SKU "test-product-red" of client "Test Client" is tagged as "dark-jedi"
        And the SKU "test-product-red" of client "Test Client" is tagged as "lightsaber"
        And the SKU "test-product-yellow" of client "Test Client" is tagged as "dark-jedi"
        And the SKU "test-product-yellow" of client "Test Client" is tagged as "lightsaber"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 1 | test-product-blue   |
            | 3 | test-product-yellow |
        Then the order "O-001" should not have tags
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Trigger by no product having tags to add tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when none of the line items have these tags
            | dark-jedi | lightsaber |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 1 | test-product-blue   |
            | 3 | test-product-yellow |
        Then the order "O-001" should have these tags
            | b2b-pipeline | requires-forklift | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Add wholesale tags: Added "b2b-pipeline, requires-forklift" tags
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not triggering by some products having tags to add tags
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when none of the line items have these tags
            | dark-jedi | lightsaber |
        And the automation adds these tags
            | b2b-pipeline | requires-forklift |
        And the SKU "test-product-red" of client "Test Client" is tagged as "dark-jedi"
        And the SKU "test-product-red" of client "Test Client" is tagged as "lightsaber"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 1 | test-product-blue   |
            | 3 | test-product-yellow |
        Then the order "O-001" should not have tags
        And the authenticated user is "roger+test_client@packiyo.com"
