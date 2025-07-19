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
        And the customer "Test 3PL Client" has a sales channel named "3plclient.shopify.com"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a shipping carrier "UPS" and a shipping method "Air"
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

    Scenario: Trying to assign a customer to a locked by action automation
        Given an order automation named "Add line item" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation adds 1 of the SKU "test-product-blue"
        And I will run the "automation:assign" command
        And I will choose 3PL "Test 3PL"
        And I will choose the automation "Add line item"
        And the command should fail with the message "This automation is locked and cannot be assigned to customers."
        When I run the command as intended
        Then these automations should not be assigned to the customer "Test 3PL Client 2"
        | Add line item |
        And these automations should not be assigned to the customer "Test 3PL Client 3"
        | Add line item |

    Scenario: Trying to assign a customer to a locked by order channel condition automation
        Given an order automation named "Random automation" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation is triggered when a new order from the channel "3plclient.shopify.com" is received
        And I will run the "automation:assign" command
        And I will choose 3PL "Test 3PL"
        And I will choose the automation "Random automation"
        And the command should fail with the message "This automation is locked and cannot be assigned to customers."
        When I run the command as intended
        Then these automations should not be assigned to the customer "Test 3PL Client 2"
        | Random automation |
        And these automations should not be assigned to the customer "Test 3PL Client 3"
        | Random automation |

    Scenario: Trying to assign a customer to a locked by order item tags condition automation
        Given an order automation named "Random tags automation" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation is triggered when a new order has a line item with these tags
        | tag1 | tag2 |
        And I will run the "automation:assign" command
        And I will choose 3PL "Test 3PL"
        And I will choose the automation "Random tags automation"
        And the command should fail with the message "This automation is locked and cannot be assigned to customers."
        When I run the command as intended
        Then these automations should not be assigned to the customer "Test 3PL Client 2"
        | Random tags automation |
        And these automations should not be assigned to the customer "Test 3PL Client 3"
        | Random tags automation |

    Scenario Outline: Trying to assign a customer to a locked by order line item condition automation
        Given an order automation named "Random line item automation" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And <trigger>
        And I will run the "automation:assign" command
        And I will choose 3PL "Test 3PL"
        And I will choose the automation "Random line item automation"
        And the command should fail with the message "This automation is locked and cannot be assigned to customers."
        When I run the command as intended
        Then these automations should not be assigned to the customer "Test 3PL Client 2"
        | Random line item automation |
        And these automations should not be assigned to the customer "Test 3PL Client 3"
        | Random line item automation |

        Examples:
            | trigger |
            | the automation is triggered when the order has the SKU "test-product-blue" |
            | the automation is triggered when the order has a total of 1 items |
            | the automation is triggered when the order has at least 1 items |
            | the automation is triggered when the order has at most 1 items |

    Scenario: Trying to assign a customer to a locked by client's shipping method action automation
        Given an order automation named "Random shipping method automation" owned by "Test 3PL" is enabled
        And the automation applies to the 3PL client "Test 3PL Client"
        And the automation sets the shipping carrier "UPS" and the shipping method "Air"
        And I will run the "automation:assign" command
        And I will choose 3PL "Test 3PL"
        And I will choose the automation "Random shipping method automation"
        And the command should fail with the message "This automation is locked and cannot be assigned to customers."
        When I run the command as intended
        Then these automations should not be assigned to the customer "Test 3PL Client 2"
        | Random shipping method automation |
        And these automations should not be assigned to the customer "Test 3PL Client 3"
        | Random shipping method automation |

