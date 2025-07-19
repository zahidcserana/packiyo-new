@public_api @users @auth
Feature: Authenticating on the Public API
    As a third party developer
    I want to be able to identify myself when interacting with the Packiyo Public API
    So that I can access and manage data private to my profile.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"

    Scenario: Getting the token's user
        When I call the "/api/v1/users/me" endpoint
        Then the response contains the field "data.type" with the value "users"
        Then the response contains the field "data.id" with the ID of the user "roger+test_client@packiyo.com"
        Then the response code is "200"
