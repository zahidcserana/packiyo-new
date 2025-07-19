@automation @cli @internal
Feature: Automation CLI
    As a Packiyo staffer
    I want to be able to assign one or more clients to an automation
    So that I can reuse an existing automation on other clients.

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
        And a customer called "Test 3PL Client 3" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49

    Scenario: Assigning an automation to a customer
        Given an order automation named "Add tags A" owned by "Test 3PL" is enabled
        And the automation applies to some 3PL clients
        And the automation applies to the 3PL client "Test 3PL Client"
        And I will run the "automation:assign" command
        And I will choose 3PL "Test 3PL"
        And I will choose the automation "Add tags A"
        And I will choose for the automation to be assigned to the 3PL clients
        | Test 3PL Client 2 |
        And the command should succeed with the message "The automation was assigned to the chosen customers."
        When I run the command as intended
        Then these automations should be assigned to the customer "Test 3PL Client 2"
        | Add tags A |
        And these automations should not be assigned to the customer "Test 3PL Client 3"
        | Add tags A |

    Scenario: Assigning an automation to two customers
        Given an order automation named "Add tags A" owned by "Test 3PL" is enabled
        And the automation applies to some 3PL clients
        And I will run the "automation:assign" command
        And I will choose 3PL "Test 3PL"
        And I will choose the automation "Add tags A"
        And I will choose for the automation to be assigned to the 3PL clients
        | Test 3PL Client | Test 3PL Client 2 |
        And the command should succeed with the message "The automation was assigned to the chosen customers."
        When I run the command as intended
        Then these automations should be assigned to the customer "Test 3PL Client"
        | Add tags A |
        And these automations should be assigned to the customer "Test 3PL Client 2"
        | Add tags A |
        And these automations should not be assigned to the customer "Test 3PL Client 3"
        | Add tags A |
