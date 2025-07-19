@3pl @billing @package @rates @shippingBoxes
Feature: Billing for packages over different periods
    As the owner of a 3PL business
    I want to be able to create package rates with different filters and different shipping boxes
    So that I can charge my customers according to each package's characteristics
    And when i get duplicates i will not be able to create a new rate.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And the customer "Test 3PL" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test 3PL Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test 3PL Client" has a shipping box named '6" x 5" x 6" Brown Box'

    Scenario: Creating two package rates with the same customer shipping boxes should generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          |                     |                         |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with different customer shipping boxes does not generate a conflict.
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl Client" shipping box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          |                     |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with same customer with different shipping boxes does not generate a conflict.
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use ship box '6" x 5" x 6" Brown Box' of the customer "Test 3pl Client"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | 6" x 6" x 6" Brown Box |                     |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with same customer with different shipping boxes with same included tags does not generate a conflict.
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use ship box '6" x 5" x 6" Brown Box' of the customer "Test 3pl Client"
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | 6" x 6" x 6" Brown Box | B2B                 |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with same customer with different shipping boxes with same included tags does not generate a conflict.
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use ship box '6" x 5" x 6" Brown Box' of the customer "Test 3pl Client"
        And the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | 6" x 6" x 6" Brown Box |                     | B2B                     |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario Outline:  Creating two package rates with same customer with same shipping boxes with same included tags does generate a conflict.
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use ship box '<ship box A>' of the customer "Test 3pl Client"
        And the package rate "Test Package Rate" applies when the order is tagged as <tag 1>
        And the package rate "Test Package Rate" applies when the order is also tagged as <tag 2>
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | <ship box B> | <order tags>        | <not order tags>        |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"
        Examples:
            | ship box A             | ship box B             | tag 1 | tag 2 | order tags | not order tags |
            | 6" x 6" x 6" Brown Box | 6" x 6" x 6" Brown Box | B2B   | B2C   | B2B,B2C    |                |

    Scenario Outline:  Creating two package rates with same customer with same shipping boxes with same included tags does not generate a conflict.
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use ship box '<ship box A>' of the customer "Test 3pl Client"
        And the package rate "Test Package Rate" applies when the order is tagged as <tag 1>
        And the package rate "Test Package Rate" applies when the order is also tagged as <tag 2>
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | <ship box B> | <order tags>        | <not order tags>        |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"
        Examples:
            | ship box A             | ship box B             | tag 1 | tag 2 | order tags | not order tags |
            | 6" x 5" x 6" Brown Box | 6" x 6" x 6" Brown Box | B2B   | B2C   | B2B,B2C    |                |
            | 6" x 5" x 6" Brown Box | 6" x 6" x 6" Brown Box | B2B   | B2C   |            | B2B,B2C        |
            | 6" x 6" x 6" Brown Box | 6" x 6" x 6" Brown Box | B2B   | B2C   |            | B2B,B2C        |

    Scenario: Creating two package rates with same customer with same shipping boxes with same included tags for included and excluded does generate a conflict.
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use ship box '6" x 6" x 6" Brown Box' of the customer "Test 3pl Client"
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the package rate "Test Package Rate" applies when the order is also tagged as "B2C"
        And the package rate "Test Package Rate" applies when the order is also tagged as "test"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | 6" x 6" x 6" Brown Box | B2B,B2C             | test                    |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer shipping boxes and the same tags for excluded tags does generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          |                     | B2B                     |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer shipping boxes with no tags does generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl client" shipping box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl client | All          |                     |                         |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer shipping boxes and the same tags for included tags does generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          | B2B                 |                         |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer shipping boxes and different tags for included and excluded does generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          |                     | B2B                     |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with different customer shipping boxes and different tags for included does not generate a conflict. Second rate will be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3PL Client | All          | B2C                 |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer shipping boxes and the same tags for excluded does generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3PL | All          |                     | B2B                     |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with different customer shipping boxes and the same tags for included tags does not generate a conflict. Second rate will be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | All          | B2B                 |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with different customer shipping boxes and the same tag for included and excluded does not generate a conflict. Second rate will be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | All          |                     | B2B                     |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer shipping boxes and the same multiple tags for included and excluded does not generate a conflict. Second rate will be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the package rate "Test Package Rate" applies when the order is also tagged as "Test"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          |                     | B2B, test               |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with same customer shipping boxes and the same multiple tags for included and excluded does not generate a conflict. Second rate will be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        And the package rate "Test Package Rate" applies when the order is also not tagged as "Test"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          | B2B, test           |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"
