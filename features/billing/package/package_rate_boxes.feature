@3pl @billing @package @rates @shippingBoxes
Feature: Billing for packages over different periods
    As the owner of a 3PL business
    I want to be able to create package rates with different shipping boxes for different customers
    So that I can charge my customers according to each package's characteristics.

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
        And the customer "Test 3PL Client" has a shipping box named '6" x 5" x 5" Brown Box'

    Scenario: Creating two package rates with same customer but different shipping boxes does generate a conflict.
    Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl | 6" x 6" x 6" Brown Box |                     |                         |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with different customer but different shipping boxes does not generate a conflict.
    Second rate will be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use any of the customer "Test 3pl" shipping box
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | 6" x 5" x 6" Brown Box |                     |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"

    Scenario: Creating two package rates with same customer and same shipping boxes does generate a conflict.
    Second rate will not be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the package rate "Test Package Rate" applies when use ship box '6" x 5" x 6" Brown Box' of the customer "Test 3pl Client"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | 6" x 5" x 6" Brown Box |                     |                         |           |
        Then the rate card "Test Rate Card" should not have a package rate called "test package 2"

    Scenario: Creating two package rates with same customer and different shipping boxes does not generate a conflict.
    Second rate will be created
        Given a package rate "Test Package Rate" on rate card "Test Rate Card"
        And the customer "Test 3PL Client" has a shipping box named '6" x 5" x 5" Brown Box'
        And the package rate "Test Package Rate" applies when use ship box '6" x 5" x 6" Brown Box' of the customer "Test 3pl Client"
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box           | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | 6" x 5" x 5" Brown Box |                     |                         |           |
        Then the rate card "Test Rate Card" should have a package rate called "test package 2"


    Scenario: Creating two package rates with same customer but one doesnt have shipping box or custom shipping selected, generates and error
        When I create package rate named "test package 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | customer        | shipping box | match_has_order_tag | match_has_not_order_tag | is_custom |
            | Test 3pl Client | none         |                     |                         |           |
        Then I should have gotten the error "Conflicts, please select a shipping package option or custom package"
        And the rate card "Test Rate Card" should not have a package rate called "test package 2"
