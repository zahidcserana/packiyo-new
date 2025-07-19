@automation @orders
Feature: Assign / Unassign automations using Public API
    As a warehouse manager
    I want to be able to Assign/Unassign customer to the automations using the Public API
    So that I can do it using an API interface.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"

    Scenario: Assign Customer to Automation using api
        Given a 3PL called "3PL Customer" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "3PL Customer"
        And an order automation named "Assign Automation Customer Test" owned by "3PL Customer" is enabled
        And a customer called "3PL Customer 2" based in "United States" client of 3PL "3PL Customer"
        And the automation applies to some 3PL clients
        When attach a Customer "3PL Customer 2" to the Automation "Assign Automation Customer Test"
        Then the response code is "200"
        And get the Customers from the Automation "Assign Automation Customer Test"
        And the response code is "200"
        And the response contains the field "data" with a reference to the customer "3PL Customer 2"

    Scenario: Unassign the Customer from Automation using api
        Given a 3PL called "3PL Customer" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "3PL Customer"
        And an order automation named "Unassign Automation Customer Test" owned by "3PL Customer" is enabled
        And the automation applies to the 3PL client "3PL Customer"
        When detach a Customer "3PL Customer" to the Automation "Unassign Automation Customer Test"
        Then the response code is "200"
        And get the Customers from the Automation "Unassign Automation Customer Test"
        And the response code is "200"
        And the response does not contain the field "data.0"

    Scenario: Assign/Unassign Customers from Automation using api
        Given a 3PL called "3PL Customer" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "3PL Customer"
        And an order automation named "Update Automation Customer Test" owned by "3PL Customer" is enabled
        And the automation applies to the 3PL client "3PL Customer"
        And a customer called "3PL Customer 2" based in "United States" client of 3PL "3PL Customer"
        When set only the Customer "3PL Customer 2" to the Automation "Update Automation Customer Test"
        Then the response code is "200"
        And get the Customers from the Automation "Update Automation Customer Test"
        And the response code is "200"
        And the response contains the field "data" with a reference to the customer "3PL Customer 2"

    Scenario: List Customers filtered by parent customer using api
        Given a 3PL called "3PL Customer 1" based in "United States"
        And a customer called "Customer 1 - 3PL Customer 1" based in "United States" client of 3PL "3PL Customer 1"
        And a customer called "Customer 2 - 3PL Customer 1" based in "United States" client of 3PL "3PL Customer 1"
        And a 3PL called "3PL Customer 2" based in "United States"
        And a customer called "Customer 3 - 3PL Customer 2" based in "United States" client of 3PL "3PL Customer 2"
        And a customer called "Customer 4 - 3PL Customer 2" based in "United States" client of 3PL "3PL Customer 2"
        And a 3PL called "3PL Customer 3" based in "United States"
        And a customer called "Customer 5 - 3PL Customer 3" based in "United States" client of 3PL "3PL Customer 3"
        And a customer called "Customer 6 - 3PL Customer 3" based in "United States" client of 3PL "3PL Customer 3"
        And the user "roger+test_client@packiyo.com" belongs to the customer "3PL Customer 2"
        When I call the customer endpoint filtering by "3PL Customer 2" parent
        Then the response code is "200"
        And the response contains the field "data.0.type" with the value "customers"
        And the response contains the field "included" with data count "2"
        And the response contains the field "included.0.attributes.name" with the value "Customer 3 - 3PL Customer 2"
        And the response contains the field "included.1.attributes.name" with the value "Customer 4 - 3PL Customer 2"
