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
        And the customer "Test 3PL Client" has a shipping box named '6" x 5" x 6" Brown Box'


    Scenario: Creating two package rates with same customer but one with generic boxes and another shipping box does not generate a conflict.
    Second rate will be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | 6" x 6" x 6" Brown Box |                     |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer one with and custom and a specific shipping boxes should not generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | 6" x 6" x 6" Brown Box |                     |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer and custom boxes should generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl |              |                     |                         | true      |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer and custom boxes should generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | 6" x 6" x 6" Brown Box |                     |                         | true      |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer and custom boxes should generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | 6" x 6" x 6" Brown Box |                     |                         | true      |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer and custom boxes should generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          |                     |                         | true      |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario Outline: Creating two package rates with the same customer and custom boxes and different tags should generate conflicts
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        And the package rate "Test Package Rate" applies when the order is <order is> as <tag>
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl |              | <order_tag>         | <not_order_tag>         | true      |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"
        Examples:
            | order is   | tag | order_tag | not_order_tag |
            | tagged     | B2B | B2B       |               |
            | not tagged | B2B |           | B2B           |

    Scenario Outline: Creating two package rates with the same customer and custom boxes and different tags should generate conflicts
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        And the package rate "Test Package Rate" applies when the order is <order is> as <tag>
        And the package rate "Test Package Rate" applies when the order is <order is 2> as <tag 2>
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl |              | <order_tag>         | <not_order_tag>         | true      |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"
        Examples:
            | order is   | tag | order is 2 | tag 2 | order_tag | not_order_tag |
            | tagged     | B2B | tagged     | B2C   | B2B,B2C   |               |
            | not tagged | B2B | not tagged | B2C   |           | B2B,B2C       |

    Scenario Outline: Creating two package rates with the same customer and custom boxes and different multiple tags should not generate conflicts
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        And the package rate "Test Package Rate" applies when the order is <order is> as <tag>
        And the package rate "Test Package Rate" applies when the order is <order is 2> as <tag 2>
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl |              | <order_tag>         | <not_order_tag>         | true      |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"
        Examples:
            | order is | tag | order is 2 | tag 2 | order_tag | not_order_tag |
            | tagged   | B2B | tagged     | B2C   |           | B2B,B2C       |
            | tagged   | B2B | not tagged | B2C   | B2C       | B2B           |

    Scenario: Creating two package rates with the same customer and custom boxes and multiple tags should not generate conflicts
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        And the package rate "Test Package Rate" applies when the order is tagged as "B2C"
        And the package rate "Test Package Rate" applies when the order is tagged as "test"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl |              | B2B,B2C             | test                    | true      |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario Outline: Creating two package rates with the same customer and custom boxes and different tags should not generate conflicts
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        And the package rate "Test Package Rate" applies when the order is <order is> as <tag>
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl |              | <order_tag>         | <not_order_tag>         | true      |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"
        Examples:
            | order is | tag | order_tag | not_order_tag |
            | tagged   | B2B |           | B2B           |
            | tagged   | B2B |           | B2C           |

    Scenario Outline: Creating two package rates with the custom boxes and a specific box and different tags should generate conflicts
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        And the package rate "Test Package Rate" applies when the order is <order is> as <tag>
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl |              | <order_tag>         | <not_order_tag>         | true      |
        Then the rate card "Test Rate Card" <should have> a package rate called "test package 2"
        Examples:
            | order is   | tag | order_tag | not_order_tag | should have     |
            | tagged     | B2B | B2B       |               | should not have |
            | not tagged | B2B |           | B2B           | should not have |
            | tagged     | B2B |           | B2B           | should have     |

    Scenario: Creating two package rates with the same customer one with and custom and a all shipping boxes and same include tag should generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        And the package rate "Test Package Rate" applies when the order is tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          | B2B                 |                         |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with the same customer one with and custom and a all shipping boxes and same excluded tag should generate a conflict. Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use custom box
        And the package rate "Test Package Rate" applies when the order is not tagged as "B2B"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | All          |                     | B2B                     |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

