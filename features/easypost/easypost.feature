@easypost
Feature: Easypost integration
    As a customer
    I want to request available shipping rates
    So that I can choose the best shipping option

    Background:
        Given a customer called "Test Client" based in "United States"
        And the customer "Test Client" has a default currency
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse address is
            | address           | zip   | city     | state | country_id |
            | 90 Belford Street | 10014 | New York | NY    | 840        |
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has a shipping box named "Standard"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the customer "Test Client" has an SKU "test-product-green" named "Test Product Green" weighing 0.50
        And I manually set 10 of "test-product-green" into "A-1" location
        And the customer has created Easypost credentials

    Scenario: Request available shipping rates, ensure correct package weight with quantity 1
        Given the customer "Test Client" got the order number "O-001" for 1 SKU "test-product-green"
        And the order "O-001" shipping address is
        | name     | address              | zip   | city     | state | country_id |
        | New York | 123 West 45th Street | 10036 | New York | NY    | 840        |
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 1 of "test-product-green" from "A-1" location
        When I want to inspect the request for available shipping rates request made to Easypost
        Then the request's parcel weight should be 0.50 oz

    Scenario: Request available shipping rates, ensure correct package weight with quantity 2
        Given the customer "Test Client" got the order number "O-001" for 2 SKU "test-product-green"
        And the order "O-001" shipping address is
        | name     | address              | zip   | city     | state | country_id |
        | New York | 123 West 45th Street | 10036 | New York | NY    | 840        |
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "test-product-green" from "A-1" location
        When I want to inspect the request for available shipping rates request made to Easypost
        Then the request's parcel weight should be 1.00 oz

    Scenario: Request available shipping rates, ensure correct package weight with quantity 2 and client's default weight unit is lb
        Given the customer "Test Client" got the order number "O-001" for 2 SKU "test-product-green"
        And the customer "Test Client" has the setting "weight_unit" set to "lb"
        And the order "O-001" shipping address is
        | name     | address              | zip   | city     | state | country_id |
        | New York | 123 West 45th Street | 10036 | New York | NY    | 840        |
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "test-product-green" from "A-1" location
        When I want to inspect the request for available shipping rates request made to Easypost
        Then the request's parcel weight should be 16.00 oz

    Scenario: Request available shipping rates, ensure correct contact name
        Given the customer "Test Client" got the order number "O-001" for 1 SKU "test-product-green"
        And the order "O-001" shipping address is
            | name     | address              | zip   | city     | state | country_id |
            | New York | 123 West 45th Street | 10036 | New York | NY    | 840        |
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 1 of "test-product-green" from "A-1" location
        When I want to inspect the request for available shipping rates request made to Easypost
        Then the request's from address name should be "Test Client"

    Scenario: Cheapest option is correct
        Given the customer "Test Client" got the order number "O-001" for 2 SKU "test-product-green"
        And the order "O-001" shipping address is
        | name     | address              | zip   | city     | state | country_id |
        | New York | 123 West 45th Street | 10036 | New York | NY    | 840        |
        And I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "test-product-green" from "A-1" location
        And I request the shipping rates for the order
        When I request the cheapest shipping rates for the order
        Then the cheapest found option should be the same as the cheapest option available among the rates


