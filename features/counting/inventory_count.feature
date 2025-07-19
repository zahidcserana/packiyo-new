@counting
Feature: Receive locations and products in correct order to count
    As a picking app user
    I want to be sure that I get locations or products in correct order to count
    So my inventory would be up to date.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 3 locations of type "A"
        And a member user "testuser@packiyo.com" named "Test User" based in "United States"
        And the user "testuser@packiyo.com" belongs to the customer "Test 3PL"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL Client" has an SKU "test-product-1" named "Test Product 1" weighing 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-2" named "Test Product 2" weighing 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-3" named "Test Product 3" weighing 8.49
        And the warehouse "Test Warehouse" has 10 SKU "test-product-1" in location "A-0001"
        And the warehouse "Test Warehouse" has 10 SKU "test-product-2" in location "A-0002"
        And the warehouse "Test Warehouse" has 10 SKU "test-product-3" in location "A-0003"
        And the user "testuser@packiyo.com" is authenticated

    # Testing locations order

    Scenario: I want to count priority locations first
        # A-0001, A-0003 have priority, A-0002 doesn't,
        # so with priority should be first
        Given the location name "A-0001" has priority counting set
        And the location name "A-0003" has priority counting set
        When I started counting 3 locations
        Then locations order should be 'A-0001, A-0003, A-0002'

    Scenario: I want to count not counted or last counted locations first
        # A-0002 not counted at all, A-0001, A-0003 have last counted set,
        # so A-0002 should be first and others should be ordered by last counted at ASC
        Given the location name "A-0001" has last counted set
        And the location name "A-0003" has last counted set
        When I started counting 3 locations
        Then locations order should be 'A-0002, A-0001, A-0003'

    Scenario: I want to count priority locations first, others in last counted asc
        # A-0002 has priority, A-0003, A-0001 doesn't,
        # so with priority should be first
        Given the location name "A-0003" has last counted set
        And the location name "A-0002" has priority counting set
        When I started counting 3 locations
        Then locations order should be 'A-0002, A-0001, A-0003'

    # Testing products order

    Scenario: I want to count priority products first
        # test-product-1, test-product-3 have priority, test-product-2 doesn't,
        # so with priority should be first
        Given the product SKU "test-product-1" has priority counting set
        And the product SKU "test-product-3" has priority counting set
        When I started counting 3 products
        Then products order should be 'test-product-1, test-product-3, test-product-2'

    Scenario: I want to count not counted or last counted products first
        # test-product- not counted at all, test-product-1, test-product-2 have last counted set,
        # so test-product-3 should be first and others should be ordered by last counted at ASC
        Given the product SKU "test-product-1" has last counted set
        And the product SKU "test-product-2" has last counted set
        When I started counting 3 products
        Then products order should be 'test-product-3, test-product-1, test-product-2'

    Scenario: I want to count priority products first, others in last counted asc
        # test-product-2 has priority, test-product-3, test-product-1 doesn't,
        # so with priority should be first
        Given the product SKU "test-product-3" has last counted set
        And the product SKU "test-product-2" has priority counting set
        When I started counting 3 products
        Then products order should be 'test-product-2, test-product-1, test-product-3'
