@3pl @billing @storage
Feature: Billing for storage by location over different periods
    As the owner of a 3PL business
    I want to be able to create charge storage rates by location types
    So that I can charge my customers for the locations they occupied.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"

    Scenario: Create one storage label rate with one location type should not generate conflict
        Given there is not a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"
        When I create a storage label rate named "Test Storage Rate" to assign to rate card "Test Rate Card" that uses this matching criteria
            | no_location | location_of_type |
            | false       | Bin              |
        Then the rate card "Test Rate Card" should have a storage label rate called "Test Storage Rate"

    Scenario: Create two storage label rates with same location type should generate conflict
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"
        And the storage by location rate "Test Storage Rate" applies to "Bin" location type
        When I create a storage label rate named "Test Storage Rate 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | no_location | location_of_type |
            | false       | Bin              |
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "Test Storage Rate 2"

    Scenario: Create two storage label rates with different location type should not generate conflict
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"
        And the storage by location rate "Test Storage Rate" applies to "Bin" location type
        When I create a storage label rate named "Test Storage Rate 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | no_location | location_of_type |
            | false       | Shelve           |
        Then the rate card "Test Rate Card" should have a storage label rate called "Test Storage Rate 2"

    Scenario: Create two storage label rates, one with Location type A and another with location type A and B should generate conflict
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"
        And the storage by location rate "Test Storage Rate" applies to "Bin" location type
        When I create a storage label rate named "Test Storage Rate 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | no_location | location_of_type |
            | false       | Bin,Shelve       |
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "Test Storage Rate 2"

    Scenario: Create one storage label rates, with non location type should not generate conflict
        Given there is not a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"
        When I create a storage label rate named "Test Storage Rate" to assign to rate card "Test Rate Card" that uses this matching criteria
            | no_location | location_of_type |
            | true        |                  |
        Then the rate card "Test Rate Card" should have a storage label rate called "Test Storage Rate"

    Scenario: Create two storage label rates, with non location type and location type bin should not generate conflict
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"
        And the storage by location rate "Test Storage Rate" applies to "Bin" location type
        When I create a storage label rate named "Test Storage Rate" to assign to rate card "Test Rate Card" that uses this matching criteria
            | no_location | location_of_type |
            | true        |                  |
        Then the rate card "Test Rate Card" should have a storage label rate called "Test Storage Rate"

    Scenario: Create one storage label rates, with non location type and location type bin  should not generate conflict
        Given there is not a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"
        When I create a storage label rate named "Test Storage Rate" to assign to rate card "Test Rate Card" that uses this matching criteria
            | no_location | location_of_type |
            | true        | Bin              |
        Then the rate card "Test Rate Card" should have a storage label rate called "Test Storage Rate"

    Scenario Outline: Create two storage label rates, with non location type and location type bin
        Given a storage by location rate "Test Storage Rate" on rate card "Test Rate Card"
        And the storage by location rate "Test Storage Rate" applies to "Bin" location type
        And the storage by location rate "Test Storage Rate" applies to generic locations
        When I create a storage label rate named "Test Storage Rate 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | no_location         | location_of_type         |
            | <no_location_value> | <location_of_type_value> |
        Then I <error_message> gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" <conclusion> "Test Storage Rate 2"
        Examples:
            | no_location_value | location_of_type_value | conclusion                              | error_message   |
            | false             | Bin                    | should not have a rate called           | should have     |
            | false             | Shelve                 | should have a storage label rate called | should not have |
            | true              |                        | should not have a rate called           | should have     |
            | true              | Bin                    | should not have a rate called           | should have     |
            | false             |                        | should not have a rate called           | should have     |
