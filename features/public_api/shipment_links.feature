@public_api @shipping
Feature: Create Shipment Links
    As a admin
    I want to add label links to my Shipments

Background:
    Given a customer called "Test Client" based in "United States"
    And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
    And the user "roger+test_client@packiyo.com" belongs to the customer "Test Client"
    And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
    And the customer "Test Client" has an SKU "test-product-red" named "Test Product Red" priced at 49.99
    And the warehouse "Test Warehouse" had quantity 30 that attached in product with location "A-1"
    And the customer "Test Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 39.99
    And the warehouse "Test Warehouse" had quantity 20 that attached in product with location "A-1"
    And the customer "Test Client" has a supplier "Supplier 1"
    And the user has an API access token named "Roger - Public API" with the ability "public-api"
    And I call the "/api/v1/users/me" endpoint

    Scenario Outline: : the admin store a new link to the shipment using public API using different type of printers
        Given customer "Test Client" has the Printer type "<printer_type>" called "<printer_name>"
        And I create a new Shipment for the customer "Test Client"
        And I Store the link "<url>" called "<link_name>" and is printable "<is_printable>" and the printer type is "<request_printer_type>" to the Shipment using public API
        Then the response contains the field "data.attributes.url" with the value "<url>"
        And the response contains the Boolean field "data.attributes.is_printable" with the value <is_printable>
        And the response contains the field "data.attributes.printer_type" with the value "<response_printer_type>"
        And the response code is "201"
        And the printing queue should have <job_count> item for the printer "<printer_name>"

        Examples:
            | url                          | link_name         | is_printable | printer_type       | request_printer_type | response_printer_type | printer_name    | job_count |
            | http://www.google.com        | google            | yes          | label_printer      | label_printer        | label_printer        | Label printer   | 1         |
            | http://www.packiyo.com       | packiyo           | yes          | slip_printer       | slip_printer         | slip_printer         | Slip printer    | 1         |
            | http://www.youtube.com       | youtube           | yes          | barcode_printer    | barcode_printer      | barcode_printer      | Barcode printer | 1         |
            | http://www.yahoo.com         | yahoo             | no           | barcode_printer    | barcode_printer      | barcode_printer      | Barcode printer | 0         |
            | http://www.test.com          | test              | no           | slip_printer       | barcode_printer      | barcode_printer      | Barcode printer | 0         |
            | http://www.default_label.com | default label     | no           | label_printer      |                      | label_printer        | Barcode printer | 0         |

    Scenario: the admin try to store without url
        Given customer "Test Client" has the Printer type "label_printer" called "Printer test"
        And I create a new Shipment for the customer "Test Client"
        And I Store the link "" called "empty" and is printable "false" and the printer type is "label_printer" to the Shipment using public API
        And the response code is "422"

    Scenario: the admin try to store without shipment
        Given customer "Test Client" has the Printer type "label_printer" called "Printer test"
        And I create a new Shipment for the customer "Test Client"
        And I delete the Shipment in scope
        And I Store the link "http://www.google.com" called "google" and is printable "true" and the printer type is "label_printer" to the Shipment using public API
        And the response code is "404"
