@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want my automations to run on orders created using the new conditions using the Frontend API
    So that I can ensure they go through the same workflows as other orders.

    Background:
        # Standalone customer
        Given a customer called "Test Client" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the user has an API access token named "Roger - Public API" with the ability "public-api"
        And the customer "Test Client" has a shipping box named '6" x 6" x 6" Brown Box'
        And the customer "Test Client" has a sales channel named "rockandroll.shopify.com"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49

    Scenario: Listing Order Number Condition
        Given an order automation named "Set Order Number" owned by "Test Client" is enabled
        And the automation is triggered when the order number is one of these
            | 1234 | 4567 | 8910 |
        When I call the "/api/frontendv1/order-number-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "order-number-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Order number"
        And the response contains the field "data.0.attributes.description" with the value "Order number is in list (1234,4567,8910)"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "some_equals"
        And the response contains the Boolean field "data.0.attributes.case_sensitive" with the value true
        And the response contains the field "data.0.attributes.order_numbers" with the values
            | 1234 | 4567 | 8910 |
        And the automation condition should be this on db
            | App\Models\AutomationTriggers\OrderTextFieldTrigger | 1 | 0 | number | some_equals | NULL | NULL | NULL | 1234,4567,8910 | NULL | NULL | NULL | true |

    Scenario: Listing automation order tags conditions
        Given an order automation named "Set shipping method" owned by "Test Client" is enabled
        And the automation is triggered when a new order has these tags
            | first-trigger-a | second-trigger-a |
        When I call the "/api/frontendv1/order-tags-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "order-tags-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Order tag(s)"
        And the response contains the field "data.0.attributes.description" with the value "Order tag(s) is in list (first-trigger-a,second-trigger-a)"
        And the response contains the field "data.0.attributes.applies_to" with the value "all"
        And the response contains the field "data.0.attributes.tags" with these tags
            | first-trigger-a | second-trigger-a |


    Scenario: Listing Sales Channel Condition
        Given an order automation named "Set Sales Channel" owned by "Test Client" is enabled
        And  the automation is triggered when the order channel name starts with one of
            | amazon | shopify |
        When I call the "/api/frontendv1/sales-channel-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "sales-channel-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Sales channel"
        And the response contains the field "data.0.attributes.description" with the value "Sales channel starts with (amazon,shopify)"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "some_starts_with"
        And the response contains the Boolean field "data.0.attributes.case_sensitive" with the value true
        And the response contains the field "data.0.attributes.sales_channels" with the values
            | amazon | shopify |
        And the automation condition should be this on db
            | App\Models\AutomationTriggers\OrderTextFieldTrigger | 1 | 0 | orderChannel.name | some_starts_with | NULL | NULL | NULL | amazon,shopify | NULL | NULL | NULL | true |

    Scenario: Listing ShipToCountry Condition
        Given an order automation named "Set ShipToCountry" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is US
        When I call the "/api/frontendv1/ship-to-country-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "ship-to-country-conditions"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "some_equals"
        And the response contains the Boolean field "data.0.attributes.case_sensitive" with the value true
        And the response contains the field "data.0.attributes.countries" with the values
            | US |
        And the automation condition should be this on db
            | App\Models\AutomationTriggers\OrderTextFieldTrigger | 1 | 0 | shippingContactInformation.country.iso_3166_2 | some_equals | NULL | NULL | NULL | US | NULL | NULL | NULL | true |

    Scenario: Listing ShipToCustomerName Condition
        Given an order automation named "Set ShipToCustomer" owned by "Test Client" is enabled
        And the automation is triggered when the ship to customer name is "Test Customer"
        When I call the "/api/frontendv1/ship-to-customer-name-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "ship-to-customer-name-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Ship-to customer name"
        And the response contains the field "data.0.attributes.description" with the value "Ship-to customer name is Test Customer"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "some_equals"
        And the response contains the Boolean field "data.0.attributes.case_sensitive" with the value true
        And the response contains the field "data.0.attributes.customer_name" with the value "Test Customer"
        And the automation condition should be this on db
            | App\Models\AutomationTriggers\OrderTextFieldTrigger | 1 | 0 | customer.contactInformation.name | some_equals | NULL | NULL | NULL | Test Customer | NULL | NULL | NULL | true |

    Scenario: Listing ShipToState Condition
        Given an order automation named "Set ShipToState" owned by "Test Client" is enabled
        And the automation is triggered when the ship to state are
            | CA | NY |
        When I call the "/api/frontendv1/ship-to-state-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "ship-to-state-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Ship-to state"
        And the response contains the field "data.0.attributes.description" with the value "Ship-to state is in list (CA,NY)"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "some_equals"
        And the response contains the Boolean field "data.0.attributes.case_sensitive" with the value true
        And the response contains the field "data.0.attributes.states" with the values
            | CA | NY |
        And the automation condition should be this on db
            | App\Models\AutomationTriggers\OrderTextFieldTrigger | 1 | 0 | shippingContactInformation.state | some_equals | NULL | NULL | NULL | CA,NY | NULL | NULL | NULL | true |

    Scenario: Listing Shipping Option Condition
        Given an order automation named "Set Shipping Option" owned by "Test Client" is enabled
        And the automation is triggered when the shipping option starts with one of
            | option1 | option2 |
        When I call the "/api/frontendv1/shipping-option-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "shipping-option-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Shipping option"
        And the response contains the field "data.0.attributes.description" with the value "Shipping option starts with (option1,option2)"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "some_starts_with"
        And the response contains the Boolean field "data.0.attributes.case_sensitive" with the value true
        And the response contains the field "data.0.attributes.shipping_options" with the values
            | option1 | option2 |
        And the automation condition should be this on db
            | App\Models\AutomationTriggers\OrderTextFieldTrigger | 1 | 0 | shipping_method_name | some_starts_with | NULL | NULL | NULL | option1,option2 | NULL | NULL | NULL | 1 |

    Scenario: Listing SubTotal Order Amount Condition
        Given an order automation named "Set SubTotal Order Amount Condition" owned by "Test Client" is enabled
        And the automation is triggered when subtotal is 299
        When I call the "/api/frontendv1/subtotal-order-amount-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "subtotal-order-amount-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Sub total order amount"
        And the response contains the field "data.0.attributes.description" with the value "Sub total order amount is 299"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "=="
        And the response contains the number field "data.0.attributes.subtotal" with the value 299
        And the automation condition should be this on db
            | App\Models\AutomationTriggers\OrderNumberFieldTrigger | 1 | 0 | subtotal | == | NULL | NULL | 299 | NULL | NULL | NULL | NULL | NULL |

    Scenario: Listing Total Order Amount Condition
        Given an order automation named "Set Total Order Amount Condition" owned by "Test Client" is enabled
        And the automation is triggered when total is 580
        When I call the "/api/frontendv1/total-order-amount-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "total-order-amount-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Total order amount"
        And the response contains the field "data.0.attributes.description" with the value "Total order amount is 580"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "=="
        And the response contains the number field "data.0.attributes.total" with the value 580
        And the automation condition should be this on db
            | App\Models\AutomationTriggers\OrderNumberFieldTrigger | 1 | 0 | total | == | NULL | NULL | 580 | NULL | NULL | NULL | NULL | NULL |
