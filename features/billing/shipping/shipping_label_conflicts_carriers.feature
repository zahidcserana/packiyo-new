@3pl @billing @shipping @rates @carriers
Feature: Billing for storage by location over different periods
    As the owner of a 3PL business
    I want to be able to create shipping label rates with different filters and different carriers
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

    Scenario: Create two shipping label rates with the same carrier should generate a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        When I create a shipping label rate named "test shipping 2" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     |                         |                     |
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping 2"

    Scenario: Create two shipping label rates with different carriers does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        When I create a shipping label rate named "test shipping 4" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | DHL     | Air              |                     |                         |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping 4"

    ## How things should be
    Scenario: Create two shipping label rates with the same carriers, and with same tags for excluded tag does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is not tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     | B2B                     |                     |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with the same carriers, and with same tags for included tag does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           | B2B                 |                         |                     |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with the same carriers, and with same tags for included tag does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     | B2B                     |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with the same carriers, and with different tags for included and excluded does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     | Test                    |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with different carriers, and with different tags for included and excluded does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        When I create a shipping label rate named "test shipping 6" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | DHL     | Air              | Test                |                         |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping 6"

    Scenario: Create two shipping label rates with the same carriers, and same tags for included and excluded does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is not tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     | B2B                     |                     |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with different carriers, and same tag for included tags does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | DHL     | Air              | B2B                 |                         |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with different carriers, and same tag for excluded tags does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is not tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     | B2B                     |                     |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with same carriers, and same tags for included tags does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "Test"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           | B2B                 |                         |                     |
            |         |                  | Test                |                         |                     |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with different carriers, and same tags for included tags does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "Test"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | DHL     | Air              | B2B                 |                         |                     |
            |         |                  | Test                |                         |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with different carriers, and same tag for included and excluded does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "Test"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | DHL     | Air              |                     | B2B                     |                     |
            |         |                  |                     | Test                    |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with same carrier, and same tag for included and excluded does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "Test"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     | B2B                     |                     |
            |         |                  |                     | Test                    |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with same carrier, and same tag for included and excluded does not generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "Test"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is also tagged as "Example"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           | B2B                 |                         |                     |
            |         |                  | Test                |                         |                     |
            |         |                  |                     | Example                 |                     |
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with different carrier, and same tag for excluded does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "DHL"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     |                         |                     |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with different carrier, and same tag for excluded does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "DHL"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            | FedEx   | Ground           |                     |                         |                     |
            | DHL     | Air              |                     |                         |                     |
        ##needs new message? or difference of conflict maybe
        Then I should have gotten the error "Conflicts with existing fee"
        And the rate card "Test Rate Card" should not have a rate called "test shipping"

    Scenario: Create two shipping label rates with different carrier, and same tag for included does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "DHL"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  | B2B                 |                         |      true               |
        ##needs new message? or difference of conflict maybe
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"

    Scenario: Create two shipping label rates with different carrier, and same tag for excluded does generates a conflict
        Given a shipping label rate "Test Shipping Label Rate" on rate card "Test Rate Card"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "FedEx"
        And the shipping label rate "Test Shipping Label Rate" applies when the carrier is "DHL"
        And the shipping label rate "Test Shipping Label Rate" applies when the order is not tagged as "B2B"
        When I create a shipping label rate named "test shipping" to assign to rate card "Test Rate Card" that uses this matching criteria
            | carrier | methods_selected | match_has_order_tag | match_has_not_order_tag | is generic shipping |
            |         |                  |                     | B2B                     |  true                   |
        ##needs new message? or difference of conflict maybe
        Then the rate card "Test Rate Card" should have a shipping label rate called "test shipping"
