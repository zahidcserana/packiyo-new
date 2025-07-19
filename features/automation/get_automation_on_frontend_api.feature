@automation @orders
Feature: Run an order automation on creation
    As a warehouse manager
    I want my automations to run on orders created using the Frontend API
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
        And the customer "Test Client" has a warehouse named "US Warehouse" in "United States"
        And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" weighing 3.99
        And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" weighing 5.99
        And the customer "Test Client" has an SKU "test-product-yellow" named "Test Product Yellow" weighing 8.49

    Scenario: Listing all automations when no automations
        When I call the "/api/frontendv1/automations?include=applies_to_customers,actions,conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data" with an empty list
        And the response does not contain the field "included"

    Scenario: Listing all automations when one automation
        Given an order automation named "ship to country method" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is "US"
        And the automation sets the shipping carrier "FedEx" and the shipping method "Ground"
        When I call the "/api/frontendv1/automations?include=applies_to_customers,actions,conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "order-automations"
        And the response contains the text field "data.0.id"
        And the response contains the number field "data.0.attributes.position" with the value 1
        And the response contains the field "data.0.attributes.target_events" with the values
            | ordercreatedevent |
            | orderupdatedevent |
        And the response contains the text field "data.0.attributes.created_at"
        And the response contains the text field "data.0.attributes.updated_at"
        And the response contains the field "data.0.relationships.applies_to_customers.data" with an empty list
        And the response contains the field "included.0.type" with the value "ship-to-country-conditions"
        And the response contains the Boolean field "included.0.attributes.case_sensitive" with the value "true"
        And the response contains the number field "included.0.attributes.position" with the value 1
        And the response contains the field "included.0.attributes.title" with the value "Ship-to country"
        And the response contains the field "included.0.attributes.description" with the value "Ship-to country is US"
        And the response contains the field "included.0.attributes.comparison_operator" with the value "some_equals"
        And the response contains the field "included.0.attributes.countries" with the values
            | US |
        And the response contains the field "included.1.type" with the value "set-shipping-method-actions"
        And the response contains the object field "included.1.relationships.shipping_method"

    Scenario: Listing automation with add line item action
        Given an order automation named "Add Line Item Action" owned by "Test Client" is enabled
        And the automation adds 15 of the SKU "test-product-blue" and force flag "on"
        When I call the "/api/frontendv1/add-line-item-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "add-line-item-actions"
        And the response contains the field "data.0.attributes.title" with the value "Add line item"
        And the response contains the field "data.0.attributes.description" with the value 'Add line item: 15 units of "Test Product Blue"'
        And the response contains the number field "data.0.attributes.quantity" with the value 15
        And the response contains the Boolean field "data.0.attributes.force" with the value "true"

    Scenario: Listing automation with add packing note action
        Given an order automation named "Add Packing Note Action" owned by "Test Client" is enabled
        And the automation adds the packing note "test note automation"
        When I call the "/api/frontendv1/automations?include=actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "add-packing-note-actions"
        And the response contains the field "included.0.attributes.title" with the value "add packing note"
        And the response contains the field "included.0.attributes.description" with the value 'add packing note: "test note automation" (append)'
        And the response contains the field "included.0.attributes.text" with the value "test note automation"
        And the response contains the field "included.0.attributes.insert_method" with the value "append"

    Scenario: Listing automation with add tags action
        Given an order automation named "Add Tags Action" owned by "Test Client" is enabled
        And the automation adds these tags
            | action-a | action-b |
        When I call the "/api/frontendv1/automations?include=actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "add-tags-actions"
        And the response contains the field "included.0.attributes.title" with the value "Add tag(s)"
        And the response contains the field "included.0.attributes.description" with the value 'Add tag(s): action-a,action-b'
        And the response contains the field "included.0.attributes.tags" with these tags
            | action-a | action-b |

    Scenario: Listing automation with cancel order action
        Given an order automation named "Cancel Order Action" owned by "Test Client" is enabled
        And the automation cancels the order
        When I call the "/api/frontendv1/automations?include=actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "cancel-order-actions"
        And the response contains the field "included.0.attributes.title" with the value "Cancel order"
        And the response contains the field "included.0.attributes.description" with the value "Cancel order"
        And the response contains the Boolean field "included.0.attributes.ignore_fulfilled" with the value "false"

    Scenario: Listing automation with mark as fulfilled action
        Given an order automation named "Mark as fulfilled Action" owned by "Test Client" is enabled
        And the automation marks the order as fulfilled
        When I call the "/api/frontendv1/automations?include=actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "mark-as-fulfilled-actions"
        And the response contains the field "included.0.attributes.title" with the value "Mark as Fulfilled"
        And the response contains the Boolean field "included.0.attributes.ignore_cancelled" with the value "false"

    Scenario: Listing automation with Set Date Field action
        Given  the customer "Test Client" has a warehouse named "Warehouse Test" in "United States"
        And an order automation named "Set Date Field Action" owned by "Test Client" is enabled
        And the automation applies to the warehouse "Warehouse Test" and adds 22 days and set time to "06:20" on the "hold_until" field
        When I call the "/api/frontendv1/automations?include=actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "set-date-field-actions"
        And the response contains the field "included.0.attributes.title" with the value "Set Date Field"
        And the response contains the field "included.0.attributes.field_name" with the value "hold_until"
        And the response contains the field "included.0.attributes.unit_of_measure" with the value "days"
        And the response contains the number field "included.0.attributes.number_field_value" with the value 22
        And the response contains the array field "included.0.attributes.text_field_values" with the value '{"time_of_day":"06:20"}'

    Scenario: Listing automation with Set Delivery Confirmation action
        Given an order automation named "Set Delivery Confirmation Action" owned by "Test Client" is enabled
        And the automation sets the delivery confirmation to "text field test"
        When I call the "/api/frontendv1/automations?include=actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "set-delivery-confirmation-actions"
        And the response contains the field "included.0.attributes.title" with the value "Set Delivery Confirmation"
        And the response contains the field "included.0.attributes.text_field_value" with the value "text field test"

    Scenario: Listing automation with Set Flag Action
        Given an order automation named "Set Fraud Action" owned by "Test Client" is enabled
        And the automation sets the flag "fraud_hold" to "on"
        When I call the "/api/frontendv1/automations?include=actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "set-fraud-hold-actions"
        And the response contains the field "included.0.attributes.title" with the value "Set fraud hold"
        And the response contains the field "included.0.attributes.description" with the value "Set fraud hold"
        And the response contains the Boolean field "included.0.attributes.on_hold" with the value "true"

    Scenario: Listing automation with Set Priority Action
        Given an order automation named "Set Priority Action" owned by "Test Client" is enabled
        And the automation sets the flag "priority" to "on"
        When I call the "/api/frontendv1/set-priority-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-priority-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set order as priority"
        And the response contains the field "data.0.attributes.description" with the value "Set order as priority"
        And the response contains the Boolean field "data.0.attributes.priority" with the value "true"

    Scenario: Listing automation with Set Operator Hold Action
        Given an order automation named "Set Operator Hold Action" owned by "Test Client" is enabled
        And the automation sets the flag "operator_hold" to "on"
        When I call the "/api/frontendv1/set-operator-hold-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-operator-hold-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set operator hold"
        And the response contains the field "data.0.attributes.description" with the value "Set operator hold"
        And the response contains the Boolean field "data.0.attributes.on_hold" with the value "true"

    Scenario: Listing automation with Set Allocation Hold Action
        Given an order automation named "Set Allocation Hold Action" owned by "Test Client" is enabled
        And the automation sets the flag "allocation_hold" to "on"
        When I call the "/api/frontendv1/set-allocation-hold-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-allocation-hold-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set allocation hold"
        And the response contains the field "data.0.attributes.description" with the value "Set allocation hold"
        And the response contains the Boolean field "data.0.attributes.on_hold" with the value "true"

    Scenario: Listing automation with Set Fraud Hold Action
        Given an order automation named "Set Fraud Hold Action" owned by "Test Client" is enabled
        And the automation sets the flag "fraud_hold" to "on"
        When I call the "/api/frontendv1/set-fraud-hold-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-fraud-hold-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set fraud hold"
        And the response contains the field "data.0.attributes.description" with the value "Set fraud hold"
        And the response contains the Boolean field "data.0.attributes.on_hold" with the value "true"

    Scenario: Listing automation with Set Payment Hold Action
        Given an order automation named "Set Payment Hold Action" owned by "Test Client" is enabled
        And the automation sets the flag "payment_hold" to "on"
        When I call the "/api/frontendv1/set-payment-hold-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-payment-hold-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set payment hold"
        And the response contains the field "data.0.attributes.description" with the value "Set payment hold"
        And the response contains the Boolean field "data.0.attributes.on_hold" with the value "true"

    Scenario: Listing automation with Add gift Note Action
        Given an order automation named "Add gift Note Action" owned by "Test Client" is enabled
        And the automation sets the field "gift_note" to "gift_note"
        When I call the "/api/frontendv1/add-gift-note-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "add-gift-note-actions"
        And the response contains the field "data.0.attributes.title" with the value "Add gift note"
        And the response contains the field "data.0.attributes.description" with the value 'Add gift note: "gift_note"'
        And the response contains the field "data.0.attributes.gift_note" with the value "gift_note"

    Scenario: Listing automation with Set Incoterms Action
        Given an order automation named "Set Incoterms Action" owned by "Test Client" is enabled
        And the automation sets the field "incoterms" to "Incoterms value"
        When I call the "/api/frontendv1/set-incoterms-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-incoterms-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set incoterms"
        And the response contains the field "data.0.attributes.description" with the value 'Set incoterms as Incoterms value'
        And the response contains the field "data.0.attributes.incoterms" with the value "Incoterms value"

    Scenario: Listing automation with Set Shipping Method
        Given a shipping carrier "FedEx" and a shipping method "Ground"
        And a shipping carrier "UPS" and a shipping method "Air"
        And the channel shipping option "Two Day" is mapped to the carrier "FedEx" and the method "Ground"
        And an order automation named "Set shipping method" owned by "Test Client" is enabled
#        And the automation is triggered when the ship to country is "US"
        And the automation sets the shipping carrier "UPS" and the shipping method "Air"
        When I call the "/api/frontendv1/set-shipping-method-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-shipping-method-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set shipping method"
        And the response contains the field "data.0.attributes.description" with the value 'Set shipping method as Air'

    Scenario: Listing automation with Set Text Field Action
        Given an order automation named "Set Text Field Action" owned by "Test Client" is enabled
        And the automation sets the field "number" to "1234"
        When I call the "/api/frontendv1/automations?include=actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "set-text-field-actions"
        And the response contains the field "included.0.attributes.title" with the value "Set Text Field"
        And the response contains the field "included.0.attributes.field_name" with the value "number"
        And the response contains the field "included.0.attributes.text_field_value" with the value "1234"

    Scenario: Listing automation with Set Packing Dimension Action
        Given an order automation named "Set Packing Dimension Action" owned by "Test Client" is enabled
        And the automation sets the packing dimensions based on the order items using the "test box" box
        When I call the "/api/frontendv1/set-packing-dimensions-actions?include=shipping-box" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-packing-dimensions-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set Packing Dimensions"
        And the response contains the field "data.0.attributes.description" with the value "Set Packing Dimensions"
        And the response contains the field "included.0.type" with the value "shipping-boxes"
        And the response contains the field "included.0.attributes.name" with the value "test box"

    Scenario: Listing automation with Set Shipping Box SchemaAction
        Given an order automation named "Set Shipping Box Schema Action" owned by "Test Client" is enabled
        And the customer "Test Client" has a shipping box named 'test box'
        And the automation sets the shipping box "test box" of customer "Test Client"
        When I call the "/api/frontendv1/set-shipping-box-actions?include=shipping-box" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-shipping-box-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set Shipping Box"
        And the response contains the field "included.0.type" with the value "shipping-boxes"
        And the response contains the field "included.0.attributes.name" with the value "test box"

    Scenario: Listing automation order flag field conditions
        Given an order automation named "Set Order Flag" owned by "Test Client" is enabled
        And the automation is triggered when an order with flag "allocation_hold" toggled "on" is received
        When I call the "/api/frontendv1/automations?include=applies_to_customers,actions,conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "order-flag-field-conditions"
        And the response contains the field "included.0.attributes.title" with the value "Order Flag"
        And the response contains the field "included.0.attributes.field_name" with the value "allocation_hold"
        And the response contains the Boolean field "included.0.attributes.flag_value" with the value true

    Scenario: Listing automation order weight conditions
        Given an order automation named "Order Weight Condition" owned by "Test Client" is enabled
        And the automation is triggered when the weight is "==" 20 "kg"
        When I call the "/api/frontendv1/automations?include=applies_to_customers,actions,conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "order-weight-conditions"
        And the response contains the field "included.0.attributes.title" with the value "Total order weight"
        And the response contains the field "included.0.attributes.description" with the value "Total order weight is 20 kilograms"
        And the response contains the field "included.0.attributes.comparison_operator" with the value "=="
        And the response contains the number field "included.0.attributes.weight" with the value 20
        And the response contains the field "included.0.attributes.unit_of_measure" with the value "kg"

    Scenario: Listing automation order line items conditions
        Given an order automation named "Quantity of Items in the Order" owned by "Test Client" is enabled
        And the automation is triggered when the order is for at least 30 units total
        When I call the "/api/frontendv1/automations?include=applies_to_customers,actions,conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "included.0.type" with the value "quantity-order-items-conditions"
        And the response contains the field "included.0.attributes.title" with the value "Quantity of items in the order"
        And the response contains the field "included.0.attributes.description" with the value "Quantity of items in the order is equal or less than 30"
        And the response contains the number field "included.0.attributes.quantity" with the value 30
        And the response contains the field "included.0.attributes.comparison_operator" with the value ">="

    Scenario: Listing automation quantity of distinct SKUs conditions
        Given an order automation named "Quantity of distinct SKUs" owned by "Test Client" is enabled
        And the automation is triggered when the order has a total of 55 items
        When I call the "/api/frontendv1/quantity-distinct-sku-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "quantity-distinct-sku-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Quantity of distinct SKUs"
        And the response contains the field "data.0.attributes.description" with the value "Quantity of distinct SKUs is 55"
        And the response contains the number field "data.0.attributes.sku" with the value 55
        And the response contains the field "data.0.attributes.comparison_operator" with the value "=="

    Scenario: Adding a tag on update is logged into the order history
        Given an order automation named "Add tag because of product" owned by "Test Client" is enabled
        And the automation is triggered when an order is updated
        And the automation is triggered when the order has the SKU "test-product-yellow"
        When I call the "/api/frontendv1/order-line-item-conditions?include=matches_products" endpoint
        And the response contains the field "data.0.type" with the value "order-line-item-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Order line item(s) (SKUs)"
        And the response contains the field "data.0.attributes.description" with the value "Order line item(s) (SKUs) is equal or less than 1"
        And the response contains the field "data.0.attributes.applies_to" with the value "some"
        And the response contains the number field "data.0.attributes.number_field_value" with the value 1
        And the response contains the field "data.0.attributes.comparison_operator" with the value ">="

    Scenario: Listing automation order tags conditions
        Given an order automation named "Set Order Tag(s)" owned by "Test Client" is enabled
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

    Scenario: Listing automation order items tags conditions
        Given an order automation named "Set Order Item Tags" owned by "Test Client" is enabled
        And the automation item is triggered when a new order has these tags
            | item-trigger-a | item-trigger-b |
        When I call the "/api/frontendv1/order-items-tags-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "order-items-tags-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Order Item Tags"
        And the response contains the field "data.0.attributes.applies_to" with the value "all"
        And the response contains the field "data.0.attributes.tags" with these tags
            | item-trigger-a | item-trigger-b |

    Scenario: Listing automation total order amount conditions
        Given an order automation named "Total Order Amount" owned by "Test Client" is enabled
        And the automation is triggered when the order total is "==" 150
        When I call the "/api/frontendv1/total-order-amount-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "total-order-amount-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Total order amount"
        And the response contains the field "data.0.attributes.description" with the value "Total order amount is 150"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "=="
        And the response contains the number field "data.0.attributes.total" with the value 150

    Scenario: Listing automation order text pattern conditions
        Given an order automation named "Set Order Text Pattern" owned by "Test Client" is enabled
        And the automation is triggered when the field "customer.contactInformation.name" on an order "matches" the pattern "test"
        When I call the "/api/frontendv1/order-text-pattern-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "order-text-pattern-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Order Text Pattern"
        And the response contains the field "data.0.attributes.field_name" with the value "customer.contactInformation.name"
        And the response contains the field "data.0.attributes.text_pattern" with the value "test"
        And the response contains the field "data.0.attributes.comparison_operator" with the value "matches"

    Scenario: Listing automation order is manual conditions
        Given an order automation named "Order is manual condition" owned by "Test Client" is enabled
        And the automation is triggered when a new manual order is created
        When I call the "/api/frontendv1/order-is-manual-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "order-is-manual-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Order is manual"
        And the response contains the field "data.0.attributes.description" with the value "Order is manual"
        And the response contains the Boolean field "data.0.attributes.flag_value" with the value true

    Scenario: Listing automation order text field conditions
        Given an order automation named "Set ship to country method" owned by "Test Client" is enabled
        And the automation is triggered when the ship to country is not "US"
        When I call the "/api/frontendv1/ship-to-country-conditions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "ship-to-country-conditions"
        And the response contains the field "data.0.attributes.title" with the value "Ship-to country"
        And the response contains the field "data.0.attributes.countries" with the values
            | US |

    Scenario: Listing automation with Set Warehouse Action
        Given an order automation named "Set Warehouse Action" owned by "Test Client" is enabled
        And the automation sets the warehouse "US Warehouse" of customer "Test Client"
        When I call the "/api/frontendv1/set-warehouse-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "set-warehouse-actions"
        And the response contains the field "data.0.attributes.title" with the value "Set warehouse"
        And the response contains the field "data.0.attributes.description" with the value 'Set warehouse as US Warehouse'
        And the response contains the field "data.0.attributes.warehouse.name" with the value "US Warehouse"

    Scenario: Listing automation with Charge AdHocRate Action
        Given a 3PL called "Test 3PL" based in "United States"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And an ad hoc rate "Manual labeling" on rate card "Test Rate Card"
        When an order automation named "Charge AdHocRate Action" owned by "Test Client" is enabled
        And the automation charges the ad hoc rate "Manual labeling"
        When I call the "/api/frontendv1/charge-ad-hoc-rate-actions" endpoint
        Then the response code is "200"
        And the response is paginated
        And the response contains the field "data.0.type" with the value "charge-ad-hoc-rate-actions"
        And the response contains the field "data.0.attributes.title" with the value "Charge Ad Hoc rate"
        And the response contains the field "data.0.attributes.description" with the value 'Charge Ad Hoc rate'
        And the response contains the number field "data.0.attributes.position" with the value 1
        And the response contains the number field "data.0.attributes.minimum" with the value 1
        And the response contains the number field "data.0.attributes.tolerance" with the value 5
        And the response contains the number field "data.0.attributes.threshold" with the value 0
