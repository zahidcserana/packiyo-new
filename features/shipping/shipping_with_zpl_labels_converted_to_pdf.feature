@convert_zpl
Feature: Convert ZPL using Labelary API and generate label with variety of formats such as PNG or PDF

    Background:
        Given a customer called "Test Client" based in "United States"
        And a shipping carrier "FedEx" and a shipping method "Ground"
        And a member user "roger@packiyo.com" named "Roger" based in "United States"
        And the customer "Test Client" has a warehouse named "Test Warehouse" in "United States"
        And the customer "Test Client" has a shipping box named 'Standard'
        And the user "roger@packiyo.com" belongs to the customer "Test Client"
        And the user "roger@packiyo.com" is authenticated
        And the warehouse "Test Warehouse" has a receiving location called "Receiving"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the customer "Test Client" has an SKU "table" named "Table" priced at 129.95 and based in "United States"
        And I manually set 10 of "table" into "A-1" location
        And an order with the number "O-001" for 2 SKU "table" is created

    Scenario: Set ZPL code with label options to convert encoded PDF using Labelary API response and
        When I want to convert simple ZPL "^XA^FO100,100^fdHello World^FS^XZ" to PDF with options
            | width | height | accept_request   |
            | 4     | 6      | application/pdf  |
        Then the method returns response that contains label content

#    Scenario: Get file content ZPL from remote url and set label options to convert encoded PDF using Labelary API response
#        When I want to convert simple ZPL remote url "https://easypost-files.s3-us-west-2.amazonaws.com/files/postage_label/20240124/af790a0077514f32b1d035d8b2795bb0.zpl" to PDF with options
#            | width | height | accept_request   |
#            | 4     | 6      | application/pdf  |
#        Then the method returns response that contains label content
#
#    Scenario: Stored shipment when storing shipment labels check if zpl converter will work on remote ZPL url and convert to PDF encoded content
#        When the customer "Test Client" has the setting "use_zpl_labels" set to "1"
#        And I want to have shipment with shipping method "Ground" on order "O-001" and I have shipment label with ZPL remote url "https://easypost-files.s3-us-west-2.amazonaws.com/files/postage_label/20240124/af790a0077514f32b1d035d8b2795bb0.zpl" and when storing I want to convert that file to PDF
#        Then the shipment label with url "https://easypost-files.s3-us-west-2.amazonaws.com/files/postage_label/20240124/af790a0077514f32b1d035d8b2795bb0.zpl" should have PDF document type and content

