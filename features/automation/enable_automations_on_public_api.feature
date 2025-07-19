@automation @orders
Feature: Enable/Disable Automations Using Public API
    As a warehouse manager
    I want my automations can be enable/disable using the Public API
    So that I can do it using an API interface.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"

    Scenario: Disable automation using api
        Given an order automation named "Enabled Automation" owned by "Test Client" is enabled
        When call the Automation endpoint to set the is_enabled field "false". Automation Name "Enabled Automation"
        Then the response code is "200"
        Then the response contains the Boolean field "data.attributes.is_enabled" with the value "false"
        Then the automation should be disabled
        And the latest audit of the automation was authored by "Roger" and logs a change in "is_enabled"

    Scenario: Disable automation for a 3PL customer using api
        Given a 3PL called "3PL Customer" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "3PL Customer"
        And an order automation named "Enabled Automation" owned by "3PL Customer" is enabled
        And a customer called "3PL Customer 2" based in "United States" client of 3PL "3PL Customer"
        And a customer called "3PL Customer 3" based in "United States" client of 3PL "3PL Customer"
        And the automation applies to some 3PL clients
        When call the Automation endpoint to set the is_enabled field "false". Automation Name "Enabled Automation"
        Then the response code is "200"
        And the response contains the Boolean field "data.attributes.is_enabled" with the value "false"
        And the automation should be disabled
        And the latest audit of the automation was authored by "Roger" and logs a change in "is_enabled"

    Scenario: Enable automation using api
        Given an order automation named "Disabled Automation" owned by "Test Client" is disabled
        When call the Automation endpoint to set the is_enabled field "true". Automation Name "Disabled Automation"
        Then the response code is "200"
        And the response contains the Boolean field "data.attributes.is_enabled" with the value "true"
        And the automation should be enabled
        And the latest audit of the automation was authored by "Roger" and logs a change in "is_enabled"

    Scenario: Enable automation for a 3PL customer using api
        Given a 3PL called "3PL Customer" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "3PL Customer"
        And an order automation named "Disabled Automation" owned by "3PL Customer" is disabled
        And the automation applies to some 3PL clients
        When call the Automation endpoint to set the is_enabled field "true". Automation Name "Disabled Automation"
        Then the response code is "200"
        And the response contains the Boolean field "data.attributes.is_enabled" with the value "true"
        And the automation should be enabled
        And the latest audit of the automation was authored by "Roger" and logs a change in "is_enabled"
