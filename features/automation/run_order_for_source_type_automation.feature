@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want to automate order management actions on creation by source type
    So that I can ensure every time an order is created or updated by the a specific source is tag.

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

    Scenario: When order is created by FORM source, and the automation is trigger for FORM source, then automation should tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created by "FORM" type
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created by source "FORM"
        Then the order "O-001" should have these tags
            | SP-Edit | Co-Pilot |

    Scenario: When order is created by API source, and the automation is trigger for API source, then automation should tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created by "API" type
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created by source "API"
        Then the order "O-001" should have these tags
            | SP-Edit | Co-Pilot |

    Scenario: When order is created by FILE source, and the automation is trigger for FILE source, then automation should tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created by "FILE" type
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created by source "FILE"
        Then the order "O-001" should have these tags
            | SP-Edit | Co-Pilot |

    Scenario: When order is created by FORM source, and the automation is trigger for FILE source, then automation should not tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created by "FILE" type
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created by source "FORM"
        Then the order "O-001" should not have these tags
            | SP-Edit | Co-Pilot |

    Scenario: When order is created by FORM source, and the automation is trigger for FILE or FORM source, then automation should tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created with at least one of these sources
            | FORM | FILE |
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created by source "FORM"
        Then the order "O-001" should have these tags
            | SP-Edit | Co-Pilot |

    Scenario: When order is created by API source, and the automation is trigger for FILE or FORM source, then automation should not tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created with at least one of these sources
            | FORM | FILE |
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created by source "API"
        Then the order "O-001" should not have these tags
            | SP-Edit | Co-Pilot |

    Scenario: When order is created by FORM source, and the automation is trigger when is not from API or FILE source, then automation should tag order
        Given an order automation named "Tag order for 3pl client" owned by "Test 3PL" is enabled
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 2" has a sales channel named "punkrock2.shopify.com"
        And the customer "Test 3PL Client 2" has an SKU "test-product-green2-two" named "Test Product Green 2" weighing 3.99
        And the automation applies to all 3PL clients
        And the automation is triggered when and order is created with none of these sources
            | API | FILE |
        And the automation adds these tags
            | SP-Edit |
        And the user "roger+test_3pl@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-purple" is created by source "FORM"
        Then the order "O-001" should have these tags
            | SP-Edit | Co-Pilot |
