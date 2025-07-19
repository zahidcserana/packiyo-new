@3pl @shipping
Feature: Create shipping box
    As the owner of a 3PL business
    I want to be able to create different shipping box definitions,
    So that i can offer my customers according to their needs.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"

    Scenario: When send request to create a shipping box with no cost, I should be able to create a shipping box
        When customer "Test 3PL" send a request to create a shipping box with these values
            | name                   | length | width | height | cost |
            | 6" x 6" x 6" Brown Box | 6      | 6     | 6      |      |
        Then a shipping box with name '6" x 6" x 6" Brown Box' for customer "Test 3PL" and with no cost should be created

    Scenario: When send request to create a shipping box with cost of 0, I should be able to create a shipping box
        When customer "Test 3PL" send a request to create a shipping box with these values
            | name                   | length | width | height | cost |
            | 6" x 6" x 6" Brown Box | 6      | 6     | 6      | 0.00 |
        Then a shipping box with name '6" x 6" x 6" Brown Box' for customer "Test 3PL" and cost "0.00" should be created

    Scenario: When send request to create a shipping box with cost, I should be able to create a shipping box
        When customer "Test 3PL" send a request to create a shipping box with these values
            | name                   | length | width | height | cost |
            | 6" x 6" x 6" Brown Box | 6      | 6     | 6      | 2.5  |
        Then a shipping box with name '6" x 6" x 6" Brown Box' for customer "Test 3PL" and cost "2.50" should be created

    Scenario: When send request to create a shipping box with no cost for 3pl client, I should be able to create a shipping box
        When customer "Test 3PL Client" send a request to create a shipping box with these values
            | name                   | length | width | height | cost |
            | 6" x 6" x 6" Brown Box | 6      | 6     | 6      |      |
        Then a shipping box with name '6" x 6" x 6" Brown Box' for customer "Test 3PL Client" and with no cost should be created

    Scenario: When send request to create a shipping box with missing properties, I should not be able to create a shipping box
        When customer "Test 3PL" send a request to create a shipping box with these values
            | name | length | width | height | cost |
            |  6" x 6" x 6" Brown Box error    |        |       |        |      |
        Then shipping box error should occur
        And shipping boxes with name '6" x 6" x 6" Brown Box error' for client "Test 3PL" is not created
