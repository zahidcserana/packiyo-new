@Admin @features
Feature: Activate or deactivate features in instance or customer
    As an admin
    I want to be able to activate or deactivate features for the current instance or the current customer
    So that those features are available/disabled for the users in the application

    Background:
        Given an admin user "test@admin.com" named "admin" based in "United States"
        And a 3PL called "Test 3PL" based in "United States"
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"

    Scenario: admin user send request to activate feature instance, features is enabled
        Given the user "test@admin.com" is authenticated
        When making request to activate "App/Features/LotTrackingConstraints" feature
        Then the instance has the feature flag "App/Features/LotTrackingConstraints" on

    Scenario: admin user send request to activate multiple feature instance, features are enabled
        Given the user "test@admin.com" is authenticated
        And the instance has the feature flag "App/Features/ReservePickingQuantities" disable
        And the instance has the feature flag "App/Features/AllowGenericOnBulkShipping" disable
        When making request to activate the following features
            | "App/Features/ReservePickingQuantities"   |
            | "App/Features/AllowGenericOnBulkShipping" |
        Then the instance has the following feature flags enabled
            | "App/Features/ReservePickingQuantities"   |
            | "App/Features/AllowGenericOnBulkShipping" |

    Scenario: admin user send request to disable feature instance, features is disable
        Given the user "test@admin.com" is authenticated
        And the instance has the feature flag "App/Features/LotTrackingConstraints" enabled
        When making request to deactivate the following features
            | "App/Features/LotTrackingConstraints" |
        Then the instance has the following feature flags disabled
            | "App/Features/LotTrackingConstraints" |

    Scenario: admin user send request to disable multiple feature instance, features are disable
        Given the user "test@admin.com" is authenticated
        And the instance has the feature flag "App/Features/ReservePickingQuantities" enabled
        And the instance has the feature flag "App/Features/AllowGenericOnBulkShipping" enabled
        When making request to deactivate the following features
            | "App/Features/ReservePickingQuantities"   |
            | "App/Features/AllowGenericOnBulkShipping" |
        Then the instance has the following feature flags disabled
            | "App/Features/ReservePickingQuantities"   |
            | "App/Features/AllowGenericOnBulkShipping" |

    Scenario: admin user send request to activate customer feature instance, feature is enabled
        Given the user "test@admin.com" is authenticated
        When making request to activate "App/Features/FirstPickFeeFix" feature for customer "Test 3pl"
        Then customer "Test 3pl" has the "App/Features/FirstPickFeeFix" feature enabled

    Scenario: admin user send request to disable customer feature instance, feature is disabled
        Given the user "test@admin.com" is authenticated
        When making request to deactivate "App/Features/FirstPickFeeFix" feature for customer "Test 3pl"
        Then customer "Test 3pl" has the "App/Features/FirstPickFeeFix" feature disabled
