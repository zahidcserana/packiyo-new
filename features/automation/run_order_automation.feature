@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want to automate order management actions on creation
    So that I can ensure those actions are accurately and efficiently performed.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a shipping carrier "Easypost" and a shipping method "Air"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has a warehouse named "US Warehouse" in "United States"
        And the customer "Test Client" has a warehouse named "NO Warehouse" in "Norway"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49
        And the customer "Test Client" has an SKU "test-product-cyan" named "Test Product Cyan" priced at 3.99
        And the customer "Test Client" has an SKU "test-product-magenta" named "Test Product Magenta" priced at 5.99
        And the customer "Test Client" has an SKU "test-product-black" named "Test Product Black" priced at 8.49

    Scenario: Adding an SKU to a manual order on standalone customer
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation adds 2 of the SKU "test-product-blue"
        When an order with the number "O-001" for 1 SKU "test-product-red" is created
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |

    Scenario: Adding an SKU to a manual order created by source FORM on standalone customer
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation adds 2 of the SKU "test-product-blue"
        When an order with the number "O-001" for 1 SKU "test-product-red" is created by source "FORM"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |

    Scenario: Adding an SKU to a manual order created by source FILE on standalone customer
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation adds 2 of the SKU "test-product-blue"
        When an order with the number "O-001" for 1 SKU "test-product-red" is created by source "FILE"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |

    Scenario: Not adding an SKU to a manual order created by source API on standalone customer
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation adds 2 of the SKU "test-product-blue"
        When an order with the number "O-001" for 1 SKU "test-product-red" is created by source "API"
        Then the order "O-001" should have a line item with 1 of the SKU "test-product-red"
        And the order "O-001" should not have these tags
            | Co-Pilot |

    Scenario: Adding an SKU to an order by sales channel on standalone customer
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation adds 2 of the SKU "test-product-blue"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-red"
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |

    Scenario: Adding configured SKU quantity to manual order on standalone customer when none exists in order
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation adds 2 of the SKU "test-product-blue"
        When an order with the number "O-001" for 1 SKU "test-product-red" is created
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |
        And the order "O-001" should have a line item with 1 of the SKU "test-product-red"

    Scenario: Limiting SKU addition to manual order on standalone customer based on automation add quantity
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation adds 3 of the SKU "test-product-blue"
        When an order with the number "O-001" for 2 SKU "test-product-blue" is created
        Then the order "O-001" should have a line item with 3 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |

    Scenario: Preventing addition of SKU to manual order on standalone customer when the order product surpasses the configured automation add quantity
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation adds 2 of the SKU "test-product-blue"
        When an order with the number "O-001" for 3 SKU "test-product-blue" is created
        Then the order "O-001" should have a line item with 3 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |

    Scenario: Preventing addition of SKU to manual order on standalone customer when the order product has the same quantity as the configured automation add quantity
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation adds 2 of the SKU "test-product-blue"
        When an order with the number "O-001" for 2 SKU "test-product-blue" is created
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |

    Scenario: Order created as fulfilled is ignored by the automation
        Given the customer "Test Client" has an SKU "test-product-virtual" named "Test Product Virtual" weighing 5.99
        And the product's type is set to virtual
        And an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation forces adding 2 of the SKU "test-product-virtual"
        But the action ignores fulfilled
        And an order with the number "O-001" for 2 SKU "test-product-virtual" is created with fulfilled status
        Then the order "O-001" should have a line item with 2 of the SKU "test-product-virtual"

    Scenario: Order created as canceled is ignored by the automation
        Given the customer "Test Client" has an SKU "test-product-virtual" named "Test Product Virtual" weighing 5.99
        And the product's type is set to virtual
        And an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation forces adding 2 of the SKU "test-product-virtual"
        But the action ignores cancelled
        And an order with the number "O-001" for 2 SKU "test-product-virtual" is created with cancelled status
        Then the order "O-001" should have a line item with 0 of the SKU "test-product-virtual"

    Scenario: Add the configured amount to manual order on standalone customer even if exceeding the order quantity when the automation forces the addition
        Given an order automation named "Add free item" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation forces adding 2 of the SKU "test-product-blue"
        When an order with the number "O-001" for 2 SKU "test-product-blue" is created
        Then the order "O-001" should have a line item with 4 of the SKU "test-product-blue"
        And the order "O-001" should have these tags
            | Co-Pilot |

    Scenario: Don't trigger when one of two exclusive criteria doesn't match
        Given an order automation named "Add wholesale tags" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when a new order has a line item with these tags
            | b2b | wholesale |
        And the automation adds 1 of the SKU "test-product-yellow"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-yellow"
        Then the order "O-001" should have a line item with 1 of the SKU "test-product-yellow"

    Scenario Outline: Trigger by flag to set flag
        Given an order automation named "Set some flags" owned by "Test Client" is enabled
        And the automation is triggered when an order with flag "<trigger flag name>" toggled "<trigger flag value>" is received
        And the automation sets the flag "<action flag name>" to "<action flag value>"
        When the channel "rockandroll.shopify.com" gets order "O-001" with flag "<trigger flag name>" toggled "<trigger flag value>"
        Then the order "O-001" should have the "<action flag name>" set to "<action flag value>"

        # TODO: Figure out how to test the commented examples.
        Examples:
            | trigger flag name | trigger flag value | action flag name | action flag value |
            | allow_partial     | on                 | priority         | on                |
            | is_wholesale      | on                 | priority         | on                |
            | fraud_hold        | on                 | allow_partial    | on                |
            | operator_hold     | on                 | fraud_hold       | on                |
            | payment_hold      | on                 | operator_hold    | on                |
            | allocation_hold   | on                 | payment_hold     | on                |
            | priority          | on                 | allocation_hold  | on                |
            # | ready_to_pick     | on                 | ready_to_ship    | on                |
            # | ready_to_ship     | on                 | ready_to_pick    | on                |
            # | allow_partial     | off                | priority         | off               |
            | fraud_hold        | off                | allow_partial    | off               |
            | operator_hold     | off                | fraud_hold       | off               |
            | payment_hold      | off                | operator_hold    | off               |
            | allocation_hold   | off                | payment_hold     | off               |
            # | priority          | off                | allocation_hold  | off               |
            | ready_to_pick     | off                | ready_to_ship    | off               |
            | ready_to_ship     | off                | ready_to_pick    | off               |
            # | allow_partial     | off                | priority         | on                |
            | fraud_hold        | off                | allow_partial    | on                |
            | operator_hold     | off                | fraud_hold       | on                |
            | payment_hold      | off                | operator_hold    | on                |
            | allocation_hold   | off                | payment_hold     | on                |
            # | priority          | off                | allocation_hold  | on                |
            # | ready_to_pick     | off                | ready_to_ship    | on                |
            # | ready_to_ship     | off                | ready_to_pick    | on                |
            | allow_partial     | on                 | priority         | off               |
            | is_wholesale      | on                 | priority         | off               |
            | fraud_hold        | on                 | allow_partial    | off               |
            | operator_hold     | on                 | fraud_hold       | off               |
            | payment_hold      | on                 | operator_hold    | off               |
            | allocation_hold   | on                 | payment_hold     | off               |
            | priority          | on                 | allocation_hold  | off               |
            | ready_to_pick     | on                 | ready_to_ship    | off               |
            | ready_to_ship     | on                 | ready_to_pick    | off               |

    Scenario: Setting a flag is logged into the order history
        Given an order automation named "Set some flags" owned by "Test Client" is enabled
        And the automation is triggered when an order with flag "fraud_hold" toggled "on" is received
        And the automation sets the flag "allocation_hold" to "on"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets order "O-001" with flag "fraud_hold" toggled "on"
        Then the order "O-001" should have the "allocation_hold" set to "on"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Set some flags: Added allocation hold
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Adding a tag because the order has a certain amount of items on creation
        Given an order automation named "Add tag because of product" owned by "Test Client" is enabled
        And the automation is triggered when the order has at least 6 items
        And the automation is triggered when the order has at most 8 items
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 3 | test-product-blue |
            | 3 | test-product-red  |
        When the order "O-001" has 1 of the SKU "test-product-yellow" added to it
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |

    Scenario: Adding a tag because the order has a certain amount of items on updating
        Given an order automation named "Add tag because of product" owned by "Test Client" is enabled
        And the automation is also triggered when an order is updated
        And the automation is triggered when the order has a total of 7 items
        And the automation adds these tags
            | contains-yellow | probably-duckling |
        And the customer "Test Client" got the order number "O-001" for 3 SKU "test-product-blue"
        And the order "O-001" requires 3 units of SKU "test-product-red"
        When the order "O-001" has 1 of the SKU "test-product-yellow" added to it
        Then the order "O-001" should have these tags
            | contains-yellow | probably-duckling | Co-Pilot |

    Scenario: Setting the shipping method by matching to the sales channel name starting with one of many
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the customer "Test Client" has a sales channel named "Amazon"
        And the customer "Test Client" has a sales channel named "Amazon CA"
        And the customer "Test Client" has a sales channel named "Amazon MX"
        And the automation is triggered when the order channel name starts with one of
            | Amazon |
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "Amazon CA" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Set shipping method: Shipping method set to "FedEx - Ground"
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Setting the shipping method by matching to the method name from the sales channel
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the ship to country is "US"
        And the automation is triggered when the sales channel requested the "FedEx Ground" shipping method
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Set shipping method: Shipping method set to "FedEx - Ground"
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Updating shipping method manually should not run the automation action
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when an order is updated
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        And the user "roger+test_client@packiyo.com" is authenticated
        And the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "USPS Test"
        When the order "O-001" has it's shipping method set to "Air" from carrier "Easypost"
        Then the order "O-001" should have the shipping carrier "Easypost" and the shipping method "Air"
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Updating shipping method manually should run the automation action when it's forced
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when an order is updated
        And the automation forces setting the shipping carrier "FedEx" and the shipping method "Ground"
        And the user "roger+test_client@packiyo.com" is authenticated
        And the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "USPS Test"
        When the order "O-001" has it's shipping method set to "Air" from carrier "Easypost"
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Adding SKU to order should run the automation action
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when an order is updated
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        And the user "roger+test_client@packiyo.com" is authenticated
        And the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "USPS Test"
        When the order "O-001" has 1 of the SKU "test-product-blue" added to it
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Setting the shipping method by the method name from the sales channel starting with one of many
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the ship to country is "US"
        And the automation is triggered when the shipping method requested by the sales channel starts with one of
            | FedEx | UPS | USPS |
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"

    Scenario: Setting the shipping method by the method name from the sales channel ending with one of many
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the ship to country is "US"
        And the automation is triggered when the shipping method requested by the sales channel ends with one of
            | Air | Ground | Expedited |
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"

    Scenario: Setting the shipping method by the method name from the sales channel containing one of many
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the ship to country is "US"
        And the automation is triggered when the shipping method requested by the sales channel contains one of
            | Express | Ground | Expedited |
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "DHL Express Air"
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"

    Scenario: Setting the shipping method by the method name from the sales channel containing one of many, case insensitive
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the ship to country is "US"
        And the automation is triggered when the shipping method requested by the sales channel contains one of
            | EXPRESS | GROUND | EXPEDITED |
        And the trigger is case insensitive
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "DHL Express Air"
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"

    Scenario: Setting the fraud hold when order is $300 or more
        Given an order automation named "Review expensive orders" owned by "Test Client" is enabled
        And the automation is triggered when the order total is ">=" 300
        And the automation is triggered when the shipping cost is "==" 0
        And the automation sets the flag "fraud_hold" to "on"
        When the customer "Test Client" gets order "O-001" with flag "fraud_hold" toggled "off" for these SKUs
            | 100 | test-product-cyan    |
            | 100 | test-product-magenta |
            | 100 | test-product-black   |
        Then the order "O-001" should have the "fraud_hold" set to "on"
        # TODO: Why is this missing the .00 suddenly?
        And the order "O-001" should have the field "total" set to 1847
        And the order "O-001" should have the field "shipping" set to 0

    Scenario: Setting the shipping method by country and weight
        Given the customer "Test Client" has the setting "weight_unit" set to "lb"
        And an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the ship to country is "US"
        And the automation is triggered when the weight is ">" 5 "lb"
        And the automation is triggered when the weight is "<=" 10 "lb"
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" for 1 SKU "test-product-red"
        Then the order "O-001" should have the shipping carrier "FedEx" and the shipping method "Ground"

    Scenario: Trigger by product tags to set the packing dimensions by order items
        Given the customer "Test Client" has an SKU "test-product-white" named "Test Product White" sized 4.00 x 2.00 x 1.00
        And the SKU "test-product-white" of client "Test Client" is tagged as "ship-ready"
        And an order automation named "Set packing dimensions" owned by "Test Client" is enabled
        And the automation is triggered when all line items have these tags
            | ship-ready |
        And the automation sets the packing dimensions based on the order items using the "Generic" box
        When the customer "Test Client" gets the order number "O-001" for these SKUs
            | 3 | test-product-white |
        # TODO: Why is this missing the .00 suddenly?
        Then the order "O-001" should have the field "packing_length" set to 4
        Then the order "O-001" should have the field "packing_width" set to 2
        Then the order "O-001" should have the field "packing_height" set to 3

    Scenario: Setting the shipping box by matching to the method name from the sales channel
        Given an order automation named "Set shipping box" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the ship to country is "US"
        And the automation is triggered when the sales channel requested the "FedEx Ground" shipping method
        And the automation sets the shipping box '6" x 6" x 6" Brown Box' of customer "Test Client"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should have the shipping box '6" x 6" x 6" Brown Box' of customer "Test Client"

    Scenario: Setting the shipping method by matching a pattern to the order number
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation is triggered when the field "number" on an order "matches" the pattern "{#}{#}{#?}-{#+}-{#+}"
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        And the user "roger+test_client@packiyo.com" is authenticated
        When an order with the number "09-10358-76659" for 1 SKU "test-product-red" is created
        Then the order "09-10358-76659" should have the shipping carrier "FedEx" and the shipping method "Ground"
        And the order "09-10358-76659" has a log entry by "Co-Pilot" that reads
            """
            Rule Set shipping method: Shipping method set to "FedEx - Ground"
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Setting the shipping method by matching a pattern to another order number
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation is triggered when the field "number" on an order "matches" the pattern "{#}{#}{#?}-{#+}-{#+}"
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        And the user "roger+test_client@packiyo.com" is authenticated
        When an order with the number "113-0992925-0764247" for 1 SKU "test-product-red" is created
        Then the order "113-0992925-0764247" should have the shipping carrier "FedEx" and the shipping method "Ground"
        And the order "113-0992925-0764247" has a log entry by "Co-Pilot" that reads
            """
            Rule Set shipping method: Shipping method set to "FedEx - Ground"
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Marking orders as fulfilled by ship to country, case sensitive
        Given an order automation named "Mark as fulfilled" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is not "CA"
        And the automation marks the order as fulfilled
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should be marked as fulfilled
        And the order "O-001" should have the field "shippingContactInformation.country.iso_3166_2" set to "US"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Mark as fulfilled: Order was fulfilled
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Marking orders as fulfilled by ship to country, case insensitive
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is not "CA"
        And the trigger is case insensitive
        And the automation marks the order as fulfilled
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should be marked as fulfilled
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Set shipping method: Order was fulfilled
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Marking orders as fulfilled by ship to countries, case insensitive
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is none of
            | CA | MX |
        And the trigger is case insensitive
        And the automation marks the order as fulfilled
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should be marked as fulfilled
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Set shipping method: Order was fulfilled
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not marking orders as fulfilled by ship to country, case sensitive
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is not "US"
        And the automation marks the order as fulfilled
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should not be marked as fulfilled
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Not marking orders as fulfilled by ship to country, case insensitive
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is not "US"
        And the trigger is case insensitive
        And the automation marks the order as fulfilled
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should not be marked as fulfilled
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Cancelling orders by ship to country
        Given an order automation named "Cancel order" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is not "CA"
        And the automation cancels the order
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should be cancelled
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Cancel order: Order was cancelled
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Hold orders by ship to continent
        Given an order automation named "Hold order" owned by "Test Client" is enabled
        And the automation is triggered when the ship to continent is not
            | Europe | America |
        And the automation sets the flag "allocation_hold" to "on"
        And the automation sets the flag "operator_hold" to "on"
        And the user "roger+test_client@packiyo.com" is authenticated
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should have the "allocation_hold" set to "on"
        Then the order "O-001" should have the "operator_hold" set to "on"
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Hold order: Added operator hold
            """
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Hold order: Added allocation hold
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Setting the delivery confirmation requirements
        Given an order automation named "Require adult signature" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation sets the delivery confirmation to "adult"
        And the user "roger+test_client@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-red" is created
        Then the order "O-001" should have the field "delivery_confirmation" set to "adult"
        And the order "O-001" should have these tags
            | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Require adult signature: Delivery confirmation set to "adult"
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Setting the shipping incoterms
        Given an order automation named "Set delivered duty paid" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        And the automation sets the field "incoterms" to "DDP"
        And the user "roger+test_client@packiyo.com" is authenticated
        When an order with the number "O-001" for 1 SKU "test-product-red" is created
        Then the order "O-001" should have the field "incoterms" set to "DDP"
        And the order "O-001" should have these tags
            | Co-Pilot |
        And the order "O-001" has a log entry by "Co-Pilot" that reads
            """
            Rule Set delivered duty paid: Incoterms set to "DDP"
            """
        And the authenticated user is "roger+test_client@packiyo.com"

    Scenario: Setting the warehouse by matching to the method name from the sales channel
        Given an order automation named "Set warehouse" owned by "Test Client" is enabled
        And the automation is triggered when a new order from the channel "rockandroll.shopify.com" is received
        And the automation is triggered when the sales channel requested the "FedEx Ground" shipping method
        And the automation sets the warehouse "NO Warehouse" of customer "Test Client"
        When the channel "rockandroll.shopify.com" gets the order number "O-001" with shipping method name "FedEx Ground"
        Then the order "O-001" should have the warehouse "NO Warehouse" of customer "Test Client"
