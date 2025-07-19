@automation @orders @tags
Feature: Trigger automations by tag, set dates
    As a warehouse manager
    I want to run automations by tags and use them to set dates
    So that I can use the flexibility afforded by tags on my workflows.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99

    Scenario Outline: Trigger by order tag to add simple time units to a field
        Given an order automation named "<action name>" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has these tags
            | b2b |
        And the automation applies to the warehouse "Test Warehouse" and adds <amount> "<time unit>" from the creation date to the "<field name>" field
        And the user "roger+test_client@packiyo.com" is authenticated
        And the user "roger+test_client@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the current UTC date is "<utc date>" and the time is "<utc time>"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b |
        Then the order "O-001" should have a "<field name>" date of "<expected date>"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            <expected log>
            """
        And the authenticated user is "roger+test_client@packiyo.com"

        # TODO think about the audit message, since it only prints the new_value, and the new value is in UTC: app/Traits/Audits/AuditTrait.php:33
        Examples:
            | action name                  | amount | time unit | field name  | utc date   | utc time | expected date       | expected log                             |
            | Add 5 minutes to hold_until  | 5      | minutes   | hold_until  | 2021-01-01 | 23:59:00 | 2021-01-02 00:04:00 | Rule Add 5 minutes to hold_until: Hold until set to "2021-01-02 00:04:00"  |
            | Add 5 minutes to ship_before | 5      | minutes   | ship_before | 2021-01-01 | 23:59:00 | 2021-01-02 00:04:00 | Rule Add 5 minutes to ship_before: Required shipping date set to "2021-01-02 00:04:00" |
            | Add 1 hour to hold_until     | 1      | hours     | hold_until  | 2021-01-01 | 23:59:00 | 2021-01-02 00:59:00 | Rule Add 1 hour to hold_until: Hold until set to "2021-01-02 00:59:00"  |
            | Add 1 hour to ship_before    | 1      | hours     | ship_before | 2021-01-01 | 23:59:00 | 2021-01-02 00:59:00 | Rule Add 1 hour to ship_before: Required shipping date set to "2021-01-02 00:59:00" |

    Scenario Outline: Trigger by order tag, add days and time to fields
        Given an order automation named "<action name>" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has these tags
            | b2b |
        And the automation applies to the warehouse "Test Warehouse" and adds <amount> days and set time to "<time>" on the "<field name>" field
        And the user "roger+test_client@packiyo.com" is authenticated
        And the user "roger+test_client@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the current UTC date is "<utc date>" and the time is "<utc time>"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b |
        Then the order "O-001" should have a "<field name>" date of "<expected date>"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            <expected log>
            """
        And the authenticated user is "roger+test_client@packiyo.com"

        Examples:
            | action name               | amount | time  | field name  | utc date   | utc time | expected date       | expected log                             |
            | Add 2 days to hold_until  | 2      | 18:30 | hold_until  | 2021-01-01 | 23:59:00 | 2021-01-03 23:30:00 | Rule Add 2 days to hold_until: Hold until set to "2021-01-03 23:30:00"  |
            | Add 2 days to ship_before | 2      | 18:30 | ship_before | 2021-01-01 | 23:59:00 | 2021-01-03 23:30:00 | Rule Add 2 days to ship_before: Required shipping date set to "2021-01-03 23:30:00" |

    Scenario Outline: Trigger by order tag, add weeks, set day and time to to fields
        Given an order automation named "<action name>" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has these tags
            | b2b |
        And the automation applies to the warehouse "Test Warehouse" and adds <amount> weeks, sets day to "<day of week>" and time to "<time>" on the "<field name>" field
        And the user "roger+test_client@packiyo.com" is authenticated
        # In 2021-01-04 the New York timezone is UTC-5, so the expected date is 2021-01-04 14:30:00
        And the user "roger+test_client@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the current UTC date is "<utc date>" and the time is "<utc time>"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b |
        Then the order "O-001" should have a "<field name>" date of "<expected date>"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            <expected log>
            """
        And the authenticated user is "roger+test_client@packiyo.com"

        Examples:
            | action name                | amount | day of week | time  | field name  | utc date   | utc time | expected date       | expected log                             |
            | Add 2 weeks to hold_until  | 1      | Monday      | 09:30 | hold_until  | 2021-01-01 | 23:59:00 | 2021-01-04 14:30:00 | Rule Add 2 weeks to hold_until: Hold until set to "2021-01-04 14:30:00"  |
            | Add 2 weeks to ship_before | 1      | Monday      | 09:30 | ship_before | 2021-01-01 | 23:59:00 | 2021-01-04 14:30:00 | Rule Add 2 weeks to ship_before: Required shipping date set to "2021-01-04 14:30:00" |

    Scenario Outline: Trigger by order tag, add months, set day and time to fields
        # This Scenario takes into account DST on New York timezone.
        # That's the reason that even though the UTC time is 23:59:00, and we set time time to 09:30, the expected date is 13:30:00,
        # Different from the above scenarios that the expected date is 14:30:00
        Given an order automation named "<action name>" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has these tags
            | b2b |
        And the automation applies to the warehouse "Test Warehouse" and adds <amount> months, sets day to "<day of month>" and time to "<time>" on the "<field name>" field
        And the user "roger+test_client@packiyo.com" is authenticated
        # In 2021-15-03 the New York timezone is UTC-4, so the expected date is 2021-03-15 13:30:00
        And the user "roger+test_client@packiyo.com" has the setting "timezone" set to "America/New_York"
        And the current UTC date is "<utc date>" and the time is "<utc time>"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with these tags
            | b2b |
        Then the order "O-001" should have a "<field name>" date of "<expected date>"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            <expected log>
            """
        And the authenticated user is "roger+test_client@packiyo.com"

        Examples:
            | action name                 | amount | day of month | time  | field name  | utc date   | utc time | expected date       | expected log                             |
            | Add 2 months to hold_until  | 2      | 15           | 09:30 | hold_until  | 2021-01-01 | 23:59:00 | 2021-03-15 13:30:00 | Rule Add 2 months to hold_until: Hold until set to "2021-03-15 13:30:00"  |
            | Add 2 months to ship_before | 2      | 15           | 09:30 | ship_before | 2021-01-01 | 23:59:00 | 2021-03-15 13:30:00 |  Rule Add 2 months to ship_before: Required shipping date set to "2021-03-15 13:30:00" |
            | 1 month, last day 31        | 1      | 31           | 09:30 | hold_until  | 2021-01-01 | 23:59:00 | 2021-02-28 14:30:00 | Rule 1 month, last day 31: Hold until set to "2021-02-28 14:30:00"  |
            | 1 month, last day 30        | 1      | 30           | 09:30 | hold_until  | 2021-01-01 | 23:59:00 | 2021-02-28 14:30:00 | Rule 1 month, last day 30: Hold until set to "2021-02-28 14:30:00"  |
            | 1 month, last day 29        | 1      | 29           | 09:30 | hold_until  | 2021-01-01 | 23:59:00 | 2021-02-28 14:30:00 | Rule 1 month, last day 29: Hold until set to "2021-02-28 14:30:00"  |
            | 1 month, last day 30 nov    | 1      | 31           | 09:30 | hold_until  | 2021-10-01 | 23:59:00 | 2021-11-30 14:30:00 | Rule 1 month, last day 30 nov: Hold until set to "2021-11-30 14:30:00"  |
            | 1 month, last day 31 dec    | 1      | 31           | 09:30 | hold_until  | 2021-11-30 | 23:59:00 | 2021-12-31 14:30:00 | Rule 1 month, last day 31 dec: Hold until set to "2021-12-31 14:30:00"  |


