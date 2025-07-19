@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want to automate order management actions on creation for 3pl clients
    So that I can ensure every time an order is created or updated by the client is tag.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has the feature flag "App\Features\CoPilot" on
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test 3PL Client"
        And the customer "Test 3PL Client" has a sales channel named "punkrock.shopify.com"
        And the customer "Test 3PL Client" has an SKU "test-product-green" named "Test Product Green" weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-purple" named "Test Product Purple" weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-orange" named "Test Product Orange" weighing 8.49
        And a customer called "Another 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Another 3PL Client" has a sales channel named "heavymetal.shopify.com"

    Scenario: When order is created by a 3pl, and the automation triggers when order is created by 3pl client, then automation should tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created by a 3pl
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created
        Then the order "O-001" should have these tags
            | SP-Edit |Co-Pilot|

    Scenario: When order is created by a 3pl, then automation is trigger by a 3pl client, the automation should not tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created by a 3pl client
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created
        Then the order "O-001" should not have these tags
            | SP-Edit |Co-Pilot|

    Scenario: When order is created by a 3pl client, then automation is trigger by a 3pl client, the automation should tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created by a 3pl client
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_client@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created
        Then the order "O-001" should have these tags
            | SP-Edit |Co-Pilot|

    Scenario: When order is updated by a 3pl and the automation triggers when order is updated by 3pl , then automation should not tag order
        Given an order automation named "Tag order for 3pl client" for update event owned by "Test 3PL" is enabled
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is updated by a 3pl
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_client@packiyo.com" is authenticated
        When the order "O-001" has 1 of the SKU "test-product-purple" added to it by "Test 3PL Client"
        Then the order "O-001" should not have these tags
            | SP-Edit |Co-Pilot|

    Scenario: When order is updated by a 3pl client and the 3pl serves multiple clients, 3pl client is authenticated then automation should not tag order
        Given a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And a member user "roger+test_client2@packiyo.com" named "eagle" based in "United States"
        And the user "roger+test_client2@packiyo.com" belongs to the customer "Test 3PL Client 2"
        And an order automation named "Tag order for 3pl client" for update event owned by "Test 3PL" is enabled
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created by a 3pl client
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_client2@packiyo.com" is authenticated
        When the order "O-001" has 1 of the SKU "test-product-green2-two" added to it by "Test 3PL Client 2"
        Then the order "O-001" should have these tags
            | SP-Edit |Co-Pilot|

