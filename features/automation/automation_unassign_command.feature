@automation @cli @internal
Feature: Automation CLI
    As a Packiyo staffer
    I want to be able to unassign one or more automations from a client
    So that I can manage which automations apply to each client.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        # 3PL and 3PL client
        And a 3PL called "Test 3PL" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49

    Scenario: Unassign an automation from a customer
        Given an order automation named "Add tags A" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation applies to the 3PL client "Test 3PL Client 2"
        And an order automation named "Add tags B" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And I will run the "automation:unassign" command
        And I will choose 3PL "Test 3PL"
        And I will choose to unassign automations from the customer "Test 3PL Client"
        And I will choose to unassign the automations
        | Add tags A |
        And the command should succeed with the message "The automations were unassigned from the chosen customer."
        When I run the command as intended
        Then these automations should not be assigned to the customer "Test 3PL Client"
        | Add tags A |
        And these automations should be assigned to the customer "Test 3PL Client"
        | Add tags B |
        And these automations should be assigned to the customer "Test 3PL Client 2"
        | Add tags A |

    Scenario: Unassign two automations from a customer
        Given an order automation named "Add tags A" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation applies to the 3PL client "Test 3PL Client 2"
        And an order automation named "Add tags B" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And an order automation named "Add tags C" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And I will run the "automation:unassign" command
        And I will choose 3PL "Test 3PL"
        And I will choose to unassign automations from the customer "Test 3PL Client"
        And I will choose to unassign the automations
        | Add tags A | Add tags B |
        And the command should succeed with the message "The automations were unassigned from the chosen customer."
        When I run the command as intended
        Then these automations should not be assigned to the customer "Test 3PL Client"
        | Add tags A |
        | Add tags B |
        And these automations should be assigned to the customer "Test 3PL Client"
        | Add tags C |
        And these automations should be assigned to the customer "Test 3PL Client 2"
        | Add tags A |
