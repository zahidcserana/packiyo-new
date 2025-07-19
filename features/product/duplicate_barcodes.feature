@product @duplicate_barcodes
Feature: Prevent duplicate barcodes for products

    Background:
        Given a customer called "Test Client" based in "United States"
        And a member user "diego+test_client@packiyo.com" named "Diego" based in "United States"
        And the user "diego+test_client@packiyo.com" belongs to the customer "Test Client"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 5 locations of type "A"
        And the customer "Test Client" has an SKU "apples" named "Apples" and barcoded "apples-barcode"
        And the customer "Test Client" has an SKU "avocados" named "Avocados" and barcoded "avocados-barcode"

    Scenario: Disabling the "Prevent duplicate barcodes" feature and adding a product with the same barcode
        When the user "diego+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has "App\Features\PreventDuplicateBarcodes" feature turned off
        And the customer "Test Client" creates an SKU "avocados-2" named "Avocados 2" and barcoded "avocados-barcode" using the store form
        Then the user validates the product store form
        And validation has "passed"
        And the user submits the product store form
        And the customer "Test Client" has 2 products with the same barcode "avocados-barcode"

    Scenario: Enabling the "Prevent duplicate barcodes" feature and adding a product with the same barcode
        When the user "diego+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has the feature flag "App\Features\PreventDuplicateBarcodes" on
        And the customer "Test Client" creates an SKU "avocados-2" named "Avocados 2" and barcoded "avocados-barcode" using the store form
        Then the user validates the product store form
        And validation has "failed"
        And there is a following error for the field "barcode"
            """
            The following products have the same barcode: avocados
            """

    Scenario: Enabling the "Prevent duplicate barcodes" feature and updating a product that had the same barcode
        When the user "diego+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "avocados-duplicated" named "Avocados supreme" and barcoded "avocados-barcode"
        And the customer "Test Client" has an SKU "apples-duplicated" named "Apples supreme" and barcoded "apples-barcode"
        And the customer "Test Client" has the feature flag "App\Features\PreventDuplicateBarcodes" on
        And the user opens product edit form for SKU "avocados-duplicated"
        And the user changes the SKU "avocados-duplicated" barcode to "apples-barcode"
        Then the user validates the product update form
        And validation has "failed"
        And there is a following error for the field "barcode"
            """
            The following products have the same barcode: apples, apples-duplicated
            """

    Scenario: Enabling the "Prevent duplicate barcodes" feature and updating a the same product
        When the user "diego+test_client@packiyo.com" is authenticated
        And the customer "Test Client" has an SKU "avocados-duplicated" named "Avocados supreme" and barcoded "avocados-barcode"
        And the customer "Test Client" has the feature flag "App\Features\PreventDuplicateBarcodes" on
        And the user opens product edit form for SKU "avocados-duplicated"
        Then the user validates the product update form
        And validation has "passed"
