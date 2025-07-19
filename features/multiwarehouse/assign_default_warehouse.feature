@defaultwarehouse
Feature: Assign default warehouse to a customer

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has a warehouse named "Test Client Warehouse" in "United States"
        And a member user "jonah@packiyo.com" named "Jonah" based in "United States"
        And the user "jonah@packiyo.com" belongs to the customer "Test 3PL"
        And a member user "adnan@packiyo.com" named "Adnan" based in "United States"
        And the user "adnan@packiyo.com" belongs to the customer "Test 3PL Client"

    Scenario: Check the order's warehouse for 3PL
        Given the warehouse "Test Warehouse" set as default to the customer "Test 3PL"
        And an order with number "ORD-1" that belongs to customer "Test 3PL"
        And the user "jonah@packiyo.com" is authenticated
        Then the warehouse "Test Warehouse" is assigned to the order "ORD-1"

    Scenario: Check the order's warehouse for Client
        Given the warehouse "Test Client Warehouse" set as default to the customer "Test 3PL Client"
        And an order with number "ORD-2" that belongs to customer "Test 3PL Client"
        And the user "adnan@packiyo.com" is authenticated
        Then the warehouse "Test Client Warehouse" is assigned to the order "ORD-2"

    Scenario: Check the order's warehouse for Client that taken from 3PL
        Given the warehouse "Test Warehouse" set as default to the customer "Test 3PL"
        And an order with number "ORD-3" that belongs to customer "Test 3PL Client"
        And the user "adnan@packiyo.com" is authenticated
        Then the warehouse "Test Warehouse" is assigned to the order "ORD-3"

