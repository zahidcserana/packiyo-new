@3pl @billing @shipping @rates @generic_carrier
Feature: Billing for storage by location over different periods
    As the owner of a 3PL business
    I want to be able to create shipping label rates with different filters and the generic carrier
    So that I can charge my customers according to each shipment's characteristics.

    ## Set up
    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a shipping carrier "DHL" and a shipping method "Air"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the customer "Test 3PL Client" has an SKU "test-kit-purple" named "Test Kit Purple" priced at 17.99
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
        And the SKU "test-product-red" is added as a component to the kit product with quantity of 2
        And the customer "Test 3PL Client" has an SKU "test-kit-green" named "Test Kit Green" priced at 14.49
        And the SKU "test-product-blue" is added as a component to the kit product with quantity of 2
        And the SKU "test-product-yellow" is added as a component to the kit product with quantity of 2
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"

    Scenario: Create shipping label rate with generic carrier should not generates a conflict
        Given that rate card "Test Rate Card" has no rates
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  |                     |                         | true                |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create shipping label rate with generic carrier and shipping should not generates a conflict
        Given that rate card "Test Rate Card" has no rates
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     |                         | true                |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create shipping label rate with generic carrier should not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when is generic shipping
        When I create a shipping label rate named "test shipping 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  |                     |                         | true                |
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario Outline: Create two shipping label rates with the generic carrier
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is <order is> as "<tag>"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  | <order_tag>         | <not_order_tag>         | true                |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"
        Examples:
            | order is | tag | order_tag | not_order_tag |
            | tagged   | B2B |           | Test          |
            | tagged   | B2B |           | B2B           |

    Scenario Outline: Create two shipping label rates with the generic carrier
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when is generic shipping
        And the shipping label rate "Test Shipping Label Rate" applies when the order is <order is> as "<tag>"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  | <order_tag>         | <not_order_tag>         | true                |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"
        Examples:
            | order is | tag | order_tag | not_order_tag |
            | tagged   | B2B |           | Test          |
            | tagged   | B2B |           | B2B           |

    Scenario Outline: Create two shipping label rates with the generic carrier
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when is generic shipping
        And the shipping label rate "Test Shipping Label Rate" applies when the order is <order is> as "<tag>"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           | <order_tag>         | <not_order_tag>         |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"
        Examples:
            | order is   | tag | order_tag | not_order_tag |
            | tagged     | B2B | B2B       |               |
            | not tagged | B2B |           | B2B           |


    Scenario Outline: Create two shipping label rates with the generic carrier
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is <order is> as "<tag>"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  | <order_tag>         | <not_order_tag>         | true                |
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"
        Examples:
            | order is   | tag | order_tag | not_order_tag |
            | not tagged | B2B |           | B2B           |
            | tagged     | B2B | B2B       |               |

    Scenario: Create two shipping label rates with the generic carrier, and multiple same tag for included tags does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "Test"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  | B2B                 |                         | true                |
            |         |                  | Test                |                         | true                |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and one with also a shipping carrier, does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     |                         | true                |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and one with different shipping carriers, does not generates a conflict
        Given  I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     |                         | true                |
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | DHL     | Air              |                     |                         | true                |
        ##needs new message? or difference of conflict maybe
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and multiple same tag for excluded tags does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is not tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also not tagged as "Test"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  |                     | B2B                     | true                |
            |         |                  |                     | Test                    | true                |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and multiple same tag for included and excluded tags does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "Test"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  |                     | B2B                     | true                |
            |         |                  |                     | Test                    | true                |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and multiple same tag for included and excluded tags does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     | B2B                     |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and multiple same tag for included and excluded tags does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     |                         |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and multiple tags does not generates a conflict.
    First Label rate contains A as tag included and B as tag excluded,
    Second Label rate contains B as tag included and A as tag excluded.
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is not tagged as "Test"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  |                     | B2B                     | true                |
            |         |                  | Test                |                         | true                |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and multiple tags does not generates a conflict.
    First Label rate contains A as tag included and B as tag excluded,
    Second Label rate contains B as tag included and A as tag excluded.
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "Test"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "example"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  | B2B                 |                         | true                |
            |         |                  | Test                |                         | true                |
            |         |                  |                     | example                 | true                |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with the generic carrier, and with different tags for included and excluded does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is not tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  | B2B                 |                         | true                |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario Outline: Create two shipping label rates with the generic carrier, and with different tags for included and excluded does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is <order is> as "<tag>"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  | <order_tag>         | <not_order_tag>         | true                |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"
        Examples:
            | order is   | tag | order_tag | not_order_tag |
            | tagged     | B2B | B2B       |               |
            | not tagged | B2B |           | B2B           |

    Scenario: Creating two shipping rates with same customer but one doesnt have shipping carrier or generic carrier selected, generates and error
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  |                     |                         |                     |
        Then I should have gotten the error "Conflicts, please select a shipping method option or generic shipping"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"
