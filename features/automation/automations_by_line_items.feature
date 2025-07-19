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

    Scenario: Adding a tag because an SKU is manually added to an existing order
        Given an order automation named "Add tag because of product" owned by "Test Client" is enabled
        And the automation is also triggered when an order is updated
        And the automation is triggered when the order has the SKU "test-product-yellow"
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        When the order "O-001" has 1 of the SKU "test-product-yellow" added to it
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |

    Scenario: Adding a tag because the total units in an order reach a quantity threshold
        Given an order automation named "Add tag when over 10 units" owned by "Test Client" is enabled
        # And the automation is also triggered when an order is updated
        And the automation is triggered when the order is for at least 10 units total
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        # When the order "O-001" has 10 of the SKU "test-product-yellow" added to it
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 3 | test-product-blue   |
            | 5 | test-product-yellow |
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |

    Scenario: Adding a tag because any line item in an order reaches a quantity threshold
        Given an order automation named "Add tag when over 10 units" owned by "Test Client" is enabled
        # And the automation is also triggered when an order is updated
        And the automation is triggered when a line item in the order is for at least 3 units
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        # When the order "O-001" has 10 of the SKU "test-product-yellow" added to it
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 1 | test-product-blue   |
            | 3 | test-product-yellow |
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |

    Scenario: Adding a tag because no line item in an order surpasses a quantity threshold
        Given an order automation named "Add tag when over 10 units" owned by "Test Client" is enabled
        # And the automation is also triggered when an order is updated
        And the automation is triggered when no line item in the order is for less than 2 units
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        # When the order "O-001" has 10 of the SKU "test-product-yellow" added to it
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 3 | test-product-red    |
            | 4 | test-product-blue   |
            | 5 | test-product-yellow |
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |

    Scenario: Adding a tag because every line item in an order matches a quantity
        Given an order automation named "Add tag when over 10 units" owned by "Test Client" is enabled
        # And the automation is also triggered when an order is updated
        And the automation is triggered when all line items in the order are for exactly 2 units
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        # When the order "O-001" has 10 of the SKU "test-product-yellow" added to it
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 2 | test-product-red    |
            | 2 | test-product-blue   |
            | 2 | test-product-yellow |
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |
