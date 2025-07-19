@login @auth @dashboard
Feature: Logging in
    As a user of Packiyo
    I want to be able to log into the web app
    So that I can use the best young WMS out there.

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "roger@packiyo.com" named "Roger" based in "United States"
        And the user "roger@packiyo.com" belongs to the customer "Test Client"

    Scenario: Log in as an admin
        Given I navigate to "/login"
        And I should see "Sign In"
        And I type "roger@packiyo.com" into "email"
        And I type "secret" into "password"
        When I press "Sign in"
        Then the path should be "/dashboard"
