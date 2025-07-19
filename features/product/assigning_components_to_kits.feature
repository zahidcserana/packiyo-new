@product @product_kits
Feature: Assigning components to kits
    As a customer
    I want to be able to assign components to the kits with the following rules:
    - Regular product cannot be turned into a kit if it's already a component under another kit.
    - Kit product cannot be added as a component.
    - Kit product need to recalculated(on hand qty) when adding or removing components
    - Components quantity on hand cannot be changed when parent kit is not placed on pending orders

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has an SKU "table-top" named "Table Top" priced at 49.99
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And the customer "Test Client" has an SKU "table-leg" named "Table Leg" priced at 19.99
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"

    Scenario: Creating product, setting it's type to kit and assigning a component
        When the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95
        And the user opens product edit form for SKU "table"
        And the user sets the product type to static kit
        And the user adds component SKU "table-top" with quantity 1 to the form data
        And the user adds component SKU "table-leg" with quantity 4 to the form data
        And the user validates the product update form
        Then validation has "passed"
        When the user submits the product update form
        Then the product type is kit
        And the kit product has 2 components

    Scenario: Shouldn't be possible to assign kit as a component to itself
        When the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95
        And the user opens product edit form for SKU "table"
        And the user sets the product type to static kit
        And the user submits the product update form
        And the user opens product edit form for SKU "table"
        And the user adds component SKU "table" with quantity 4 to the form data
        And the user validates the product update form
        Then validation has "failed"
        And there is a following error for the field "kit_items.0.id"
            """
            kit_items.0.id cannot be a component - it's already a kit.
            """

    Scenario: Shouldn't be possible to assign different kit as a component
        When the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95
        And the user opens product edit form for SKU "table"
        And the user sets the product type to static kit
        And the user adds component SKU "table-top" with quantity 1 to the form data
        And the user adds component SKU "table-leg" with quantity 4 to the form data
        And the user validates the product update form
        Then validation has "passed"
        When the user submits the product update form
        Then the product type is kit
        And the kit product has 2 components
        When the customer "Test Client" has an SKU "table-x2" named "Table X 2" priced at 259.9
        And the user opens product edit form for SKU "table-x2"
        And the user sets the product type to static kit
        And the user adds component SKU "table" with quantity 2 to the form data
        And the user validates the product update form
        Then validation has "failed"
        And there is a following error for the field "kit_items.0.id"
            """
            kit_items.0.id cannot be a component - it's already a kit.
            """

    Scenario: Shouldn't be possible to set type to static kit for a product that already is component
        When the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95
        And the user opens product edit form for SKU "table"
        And the user sets the product type to static kit
        And the user adds component SKU "table-top" with quantity 1 to the form data
        And the user adds component SKU "table-leg" with quantity 4 to the form data
        And the user validates the product update form
        Then validation has "passed"
        When the user submits the product update form
        Then the product type is kit
        And the kit product has 2 components
        And the user opens product edit form for SKU "table-top"
        And the user sets the product type to static kit
        And the user validates the product update form
        Then validation has "failed"
        And there is a following error for the field "type"
            """
            table-top cannot be a kit - it's already a component.
            """

    @allocate_kit_component
    Scenario: Creating two kits with quantities that are on hand with same two components, changing components quantity check order line items are they allocating
        When the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95
        And the user opens product edit form for SKU "table"
        And the user sets the product type to static kit
        And the user adds component SKU "table-top" with quantity 1 to the form data
        And the user adds component SKU "table-leg" with quantity 4 to the form data
        And the user validates the product update form
        Then validation has "passed"
        When the user submits the product update form
        Then the product type is kit
        And the kit product has 2 components
        When the customer "Test Client" has an SKU "wood-table" named "Wood table" priced at 159.95
        And the user opens product edit form for SKU "wood-table"
        And the user sets the product type to static kit
        And the user adds component SKU "table-top" with quantity 2 to the form data
        And the user adds component SKU "table-leg" with quantity 8 to the form data
        And the user validates the product update form
        Then validation has "passed"
        When the user submits the product update form
        Then the product type is kit
        And the kit product has 2 components
        When the customer "Test Client" got the order number "O-001" for 1 SKU "wood-table"
        And the customer "Test Client" got the order number "O-002" for 2 SKU "table"
        Then the product SKU "wood-table" is allocated with quantity of 1
        And the product SKU "table" is allocated with quantity of 2
        And the product SKU "table-top" is allocated with quantity of 4
        And the product SKU "table-leg" is allocated with quantity of 16

    @allocate_kit_component
    Scenario: Changing components on a kit should recalculate on hand value of the kit product
        When the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" start to create an SKU "table" named "Table" priced at 129.95 and set product type as "static_kit"
        And the user adds component SKU "table-top" with quantity 0 to the form data
        And the user validates the product store form
        Then validation has "passed"
        And the user submits the product store form
        And the kit product has 1 components
        # Update first kit with quantity on hand should be calculated
        When the user opens product edit form for SKU "table"
        And the user adds component SKU "table-top" with quantity 4 to the form data
        And the user validates the product update form
        Then validation has "passed"
        Then the user submits the product update form
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table" is on hand with quantity of 25
        # Again update first kit with different quantity and on hand should be calculated with selected value
        When the user adds component SKU "table-top" with quantity 6 to the form data
        And the user validates the product update form
        Then validation has "passed"
        Then the user submits the product update form
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table" is on hand with quantity of 16

    @allocate_kit_component
    Scenario: Removing one of components on a kit should recalculate on hand value of the kit product
        When the user "roger+test_client@packiyo.com" is authenticated
        And the customer "Test Client" start to create an SKU "table" named "Table" priced at 129.95 and set product type as "static_kit"
        And the user adds component SKU "table-top" with quantity 0 to the form data
        And the user validates the product store form
        Then validation has "passed"
        And the user submits the product store form
        And the kit product has 1 components
        # Attach two components and kit quantity on hand should be calculated
        When the user opens product edit form for SKU "table"
        And the user adds component SKU "table-top" with quantity 2 to the form data
        And the user adds component SKU "table-leg" with quantity 8 to the form data
        And the user validates the product update form
        Then validation has "passed"
        And the user submits the product update form
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table-leg" is on hand with quantity of 100
        And the product SKU "table" is on hand with quantity of 12
        And the kit product has 2 components
        # Removing second component and kit quantity on hand should be calculated
        When the user opens product edit form for SKU "table"
        And the user adds component SKU "table-top" with quantity 2 to the form data
        And the user validates the product update form
        Then validation has "passed"
        And the user submits the product update form
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table" is on hand with quantity of 50
        And the kit product has 1 components

    @allocate_kit_component @kit_to_regular
    Scenario: Static kit type is changed to regular then product qty on hand should be recalculated and product component should be removed
        When the user "roger+test_client@packiyo.com" is authenticated
        Then the customer "Test Client" start to create an SKU "table" named "Table" priced at 129.95 and set product type as "static_kit"
        And the user adds component SKU "table-top" with quantity 0 to the form data
        And the user validates the product store form
        And validation has "passed"
        Then the user submits the product store form
        And the kit product has 1 components
        # Attach two components and kit quantity on hand should be calculated
        When the user opens product edit form for SKU "table"
        And the user adds component SKU "table-top" with quantity 2 to the form data
        And the user adds component SKU "table-leg" with quantity 8 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table-leg" is on hand with quantity of 100
        And the product SKU "table" is on hand with quantity of 12
        And the kit product has 2 components
        # Change product type to regular
        When the user opens product edit form for SKU "table"
        And the user sets the product type to regular
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        And the product SKU "table" is on hand with quantity of 0
        And the kit product has 0 components
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table-leg" is on hand with quantity of 100
        # Update product inventory
        When the user opens product edit form for SKU "table"
        And the user from warehouse "Test Warehouse" adds inventory in location "A-1-0001" with quantity of 10 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        And the product SKU "table" is on hand with quantity of 10
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table-leg" is on hand with quantity of 100

    @allocate_kit_component @regular_to_kit
    Scenario: Store new regular product add attach inventory in location check qty on hand,
        then change product type to static kit check if product qty on hand is recalculated,
        then adds components to updated kit and check if recalculate qty on hand in kit
        When the user "roger+test_client@packiyo.com" is authenticated
        Then the customer "Test Client" start to create an SKU "table-in-one" named "Table in one" priced at 299.99 and set product type as "regular"
        And the user validates the product store form
        And validation has "passed"
        And the user submits the product store form
        # Update product to add on location qty on hand
        When the user opens product edit form for SKU "table-in-one"
        And the user from warehouse "Test Warehouse" adds inventory in location "A-1-0001" with quantity of 10 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        And the product SKU "table-in-one" is on hand with quantity of 10
        # Change product type to kit
        When the user opens product edit form for SKU "table-in-one"
        And the user sets the product type to static kit
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        And the product SKU "table-in-one" is on hand with quantity of 0
        And the product SKU "table-in-one" in location "A-1-0001" is on hand with quantity of 10
        # Attach two components and kit quantity on hand should be calculated
        When the user opens product edit form for SKU "table-in-one"
        And the user adds component SKU "table-top" with quantity 2 to the form data
        And the user adds component SKU "table-leg" with quantity 8 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table-leg" is on hand with quantity of 100
        And the product SKU "table-in-one" is on hand with quantity of 12
        And the kit product has 2 components
        # Removing second component and kit quantity on hand should be calculated
        When the user opens product edit form for SKU "table-in-one"
        And the user adds component SKU "table-top" with quantity 2 to the form data
        And the user validates the product update form
        And validation has "passed"
        Then the user submits the product update form
        And the product SKU "table-top" is on hand with quantity of 100
        And the product SKU "table-in-one" is on hand with quantity of 50
        And the kit product has 1 components

