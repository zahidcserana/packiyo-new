# adding @skip-ci until we switch calling real postman API with VCR.
@public_api @public_api_external_carrier_credentials @skip-ci
Feature: Import external carrier credentials
    As a merchant
    I want to create update and delete external carrier credentials with public API

    Background:
    # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And I call the "/api/v1/users/me" endpoint

    Scenario: the customer store external carrier credentials using public API
        When I pass in request body the data attributes
            | reference                 | ref-001                           |
            | create_shipment_label_url | https://shipment-label.url/1234   |
            | create_return_label_url   | https://return-label.url/1234     |
            | void_label_url            | https://void-label.url/1234       |
        And I store the external carrier credential using public API
        Then the response code is "201"
        And the response contains the field "data.attributes.reference" with the value "ref-001"

    Scenario: the customer updates external carrier credentials using public API
        When the customer "Test Client" has external carrier credential with reference "ref-001"
        And I pass in request body the data attributes
            | create_shipment_label_url | https://updated-shipment-label.url/1234   |
            | create_return_label_url   | https://updated-return-label.url/1234     |
            | void_label_url            | https://updated-void-label.url/1234       |
        And I update the external carrier credentials using public API
        Then the response code is "200"
        And the response contains the field "data.attributes.create_shipment_label_url" with the value "https://updated-shipment-label.url/1234"
        And the response contains the field "data.attributes.create_return_label_url" with the value "https://updated-return-label.url/1234"
        And the response contains the field "data.attributes.void_label_url" with the value "https://updated-void-label.url/1234"

    Scenario: the customer deletes external carrier credentials using public API
        When the customer "Test Client" has external carrier credential with reference "ref-001"
        And I delete the external carrier credential using public API
        Then the response code is "204"

    Scenario: the customer stores external carrier credentials with valid get carriers url then gets response and store shipping carriers with methods using public API
        When I pass in request body the data attributes
            | reference                 | ref-001                                                                   |
            | get_carriers_url          | https://c6faeac9-9cbe-4542-ae74-d43c09e9cfd6.mock.pstmn.io/get_carriers   |
        And I store the external carrier credential using public API
        Then the response code is "201"
        And the response contains the field "data.attributes.reference" with the value "ref-001"
        And the response contains the field "data.attributes.get_carriers_url" with the value "https://c6faeac9-9cbe-4542-ae74-d43c09e9cfd6.mock.pstmn.io/get_carriers"
        And the app should have logged a "info" with the following message
            """
            [External carrier] send
            """
        And I expect to have 1 shipping carriers with 2 shipping methods
