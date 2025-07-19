@product @customs_price
Feature: Product customs price
    As a customer
    I want to be able to set customs price for a product
    So that I can calculate the customs price for an order

    Background:
        Given a customer called "3PL Test" based in "United States"
        And the customer "3PL Test" has a default currency
        And a member user "roger+test_client@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_client@packiyo.com" belongs to the customer "3PL Test"
        And the customer "3PL Test" has a warehouse named "Test Warehouse" in "United States"
        And the customer "3PL Test" has a shipping box named "Standard"
        And the warehouse "Test Warehouse" has a pickable location called "A-1"
        And the warehouse "Test Warehouse" has a pickable location called "A-2"
        And the customer "3PL Test" has the feature flag "App\Features\NewCustomsPrice" on

    Scenario Outline: Order with one line item, 1 quantity of a simple product
        Given the customer "3PL Test" has an SKU "table" named "Table" priced at <product price>
        And the product's customs price is <customs price>
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        When the customer "3PL Test" gets the order number "O-001" for these SKUs
            | <quantity> | table | <order item price> |
        Then the order "O-001" line item "table" must have customs price of <expected customs price>

        Examples:
            | quantity | product price | order item price | customs price | expected customs price |
            | 1        | 49.99         | 65.60            | 0.00          | 49.99                  |
            | 1        | 49.99         | 65.60            | 55.50         | 55.50                  |
            | 1        | 49.99         | 65.60            | null          | 49.99                  |
            | 2        | 49.99         | 131.20           | 0.00          | 49.99                  |
            | 2        | 49.99         | 65.60            | 55.50         | 55.50                  |
            | 2        | 49.99         | 65.60            | null          | 49.99                  |

    Scenario: Shipping a order through Easypost with one line item, 2 quantity of a simple product
        Given a shipping carrier "EasyPost" and a shipping method "Ground"
        And the shipping carrier "EasyPost" has Easypost credentials
        And the customer "3PL Test" has an SKU "table" named "Table" priced at 59.99
        And the product's customs price is 65.99
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And an order with the number "O-001" for 2 SKU "table" is created
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        When the order "O-001" shipment request body for EasypostShippingProvider is generated using shipping method "Ground"
        Then the Easypost shipment request body should have
            | code  | quantity | value  |
            | table | 2        | 131.98 |

    Scenario: Shipping a order through Easypost with one line item, 1 quantity of a kit with three components
        Given a shipping carrier "EasyPost" and a shipping method "Ground"
        And the shipping carrier "EasyPost" has Easypost credentials
        And the customer "3PL Test" has an SKU "table-feet" named "Table (Feet)" priced at 29.90
        And the customer "3PL Test" has an SKU "table-corners" named "Table (Corner)" priced at 19.90
        And the customer "3PL Test" has an SKU "table-connector" named "Table (Connector)" priced at 9.90
        And the customer "3PL Test" has an SKU "table" named "Table" priced at 49.99
        And the product's customs price is 79.99
        And the product with SKU "table" is a parent kit
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And the SKU "table-feet" is added as a component to the kit product with quantity of 4
        And the SKU "table-corners" is added as a component to the kit product with quantity of 4
        And the SKU "table-connector" is added as a component to the kit product with quantity of 4
        And an order with the number "O-001" for 1 SKU "table" is created
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 4 of "table-feet" from "A-1-0001" location
        And I pack 4 of "table-corners" from "A-1-0001" location
        And I pack 4 of "table-connector" from "A-1-0001" location
        When the order "O-001" shipment request body for EasypostShippingProvider is generated using shipping method "Ground"
        Then the Easypost shipment request body should have
            | code            | quantity | value |
            | table-feet      | 4        | 26.68 |
            | table-corners   | 4        | 26.68 |
            | table-connector | 4        | 26.68 |

    Scenario: Shipping a order through Webshipper with one line item, 2 quantity of a simple product
        Given a shipping carrier "Tribird" and a shipping method "Ground"
        And the shipping carrier "Tribird" has Easypost credentials
        And the customer "3PL Test" has an SKU "table" named "Table" priced at 59.99
        And the product's customs price is 65.99
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And an order with the number "O-001" for 2 SKU "table" is created
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        When the order "O-001" shipment request body for TribirdShippingProvider is generated using shipping method "Ground"
        Then the Tribird shipment request body should have
            | sku   | quantity | price |
            | table | 2        | 65.99 |

    Scenario: Shipping a order through Webshipper with one line item, 1 quantity of a kit with three components
        Given a shipping carrier "Tribird" and a shipping method "Ground"
        And the shipping carrier "Tribird" has Tribird credentials
        And the customer "3PL Test" has an SKU "table-feet" named "Table (Feet)" priced at 29.90
        And the customer "3PL Test" has an SKU "table-corners" named "Table (Corner)" priced at 19.90
        And the customer "3PL Test" has an SKU "table-connector" named "Table (Connector)" priced at 9.90
        And the customer "3PL Test" has an SKU "table" named "Table" priced at 49.99
        And the product's customs price is 79.99
        And the product with SKU "table" is a parent kit
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And the SKU "table-feet" is added as a component to the kit product with quantity of 4
        And the SKU "table-corners" is added as a component to the kit product with quantity of 4
        And the SKU "table-connector" is added as a component to the kit product with quantity of 4
        And an order with the number "O-001" for 1 SKU "table" is created
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 4 of "table-feet" from "A-1-0001" location
        And I pack 4 of "table-corners" from "A-1-0001" location
        And I pack 4 of "table-connector" from "A-1-0001" location
        When the order "O-001" shipment request body for TribirdShippingProvider is generated using shipping method "Ground"
        Then the Tribird shipment request body should have
            | sku             | quantity | price |
            | table-feet      | 4        | 6.67  |
            | table-corners   | 4        | 6.67  |
            | table-connector | 4        | 6.67  |

    Scenario: Shipping a order though ExternalCarrier with one line item, 2 quantity of a simple product
        Given a shipping carrier "Webshipper" and a shipping method "Ground"
        And the shipping carrier "Webshipper" has the settings
            | external_carrier_id |
            | 1                   |
        And the shipping method "Ground" from "Webshipper" has the settings
            | external_method_id |
            | 1                  |
        And the shipping carrier "Webshipper" has Webshipper credentials
        And the customer "3PL Test" has an SKU "table" named "Table" priced at 59.99
        And the product's customs price is 65.99
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And an order with the number "O-001" for 2 SKU "table" is created
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        When the order "O-001" shipment request body for WebshipperShippingProvider is generated using shipping method "Ground"
        Then the Webshipper shipment request body should have
            | sku   | quantity | unit_price |
            | table | 2        | 65.99      |

    Scenario: Shipping a order though ExternalCarrier with one line item, 1 quantity of a kit with three components
        Given a shipping carrier "Webshipper" and a shipping method "Ground"
        And the shipping carrier "Webshipper" has the settings
            | external_carrier_id |
            | 1                   |
        And the shipping method "Ground" from "Webshipper" has the settings
            | external_method_id |
            | 1                  |
        And the shipping carrier "Webshipper" has Webshipper credentials
        And the customer "3PL Test" has an SKU "table-feet" named "Table (Feet)" priced at 29.90
        And the customer "3PL Test" has an SKU "table-corners" named "Table (Corner)" priced at 19.90
        And the customer "3PL Test" has an SKU "table-connector" named "Table (Connector)" priced at 9.90
        And the customer "3PL Test" has an SKU "table" named "Table" priced at 49.99
        And the product's customs price is 79.99
        And the product with SKU "table" is a parent kit
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And the SKU "table-feet" is added as a component to the kit product with quantity of 4
        And the SKU "table-corners" is added as a component to the kit product with quantity of 4
        And the SKU "table-connector" is added as a component to the kit product with quantity of 4
        And an order with the number "O-001" for 1 SKU "table" is created
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 4 of "table-feet" from "A-1-0001" location
        And I pack 4 of "table-corners" from "A-1-0001" location
        And I pack 4 of "table-connector" from "A-1-0001" location
        When the order "O-001" shipment request body for WebshipperShippingProvider is generated using shipping method "Ground"
        Then the Webshipper shipment request body should have
            | sku             | quantity | unit_price |
            | table-feet      | 4        | 6.67       |
            | table-corners   | 4        | 6.67       |
            | table-connector | 4        | 6.67       |

    Scenario: Shipping a order though ExternalCarrier with one line item, 2 quantity of a simple product
        Given a shipping carrier "ExternalCarrier" and a shipping method "Ground"
        And the shipping carrier "ExternalCarrier" has Easypost credentials
        And the customer "3PL Test" has an SKU "table" named "Table" priced at 59.99
        And the product's customs price is 65.99
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And an order with the number "O-001" for 2 SKU "table" is created
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 2 of "table" from "A-1" location
        When the order "O-001" shipment request body for ExternalCarrierShippingProvider is generated using shipping method "Ground"
        Then the ExternalCarrier shipment request body should have
            | sku   | quantity | unit_price |
            | table | 2        | 65.99      |

    Scenario: Shipping a order though ExternalCarrier with one line item, 1 quantity of a kit with three components
        Given a shipping carrier "ExternalCarrier" and a shipping method "Ground"
        # And the shipping carrier "ExternalCarrier" has ExternalCarrier credentials
        And the customer "3PL Test" has an SKU "table-feet" named "Table (Feet)" priced at 29.90
        And the customer "3PL Test" has an SKU "table-corners" named "Table (Corner)" priced at 19.90
        And the customer "3PL Test" has an SKU "table-connector" named "Table (Connector)" priced at 9.90
        And the customer "3PL Test" has an SKU "table" named "Table" priced at 49.99
        And the product's customs price is 79.99
        And the product with SKU "table" is a parent kit
        And the warehouse "Test Warehouse" had quantity 100 that attached in product with location "A-1"
        And the SKU "table-feet" is added as a component to the kit product with quantity of 4
        And the SKU "table-corners" is added as a component to the kit product with quantity of 4
        And the SKU "table-connector" is added as a component to the kit product with quantity of 4
        And an order with the number "O-001" for 1 SKU "table" is created
        When I start packing order "O-001"
        And I take box "Standard"
        And I pack 4 of "table-feet" from "A-1-0001" location
        And I pack 4 of "table-corners" from "A-1-0001" location
        And I pack 4 of "table-connector" from "A-1-0001" location
        When the order "O-001" shipment request body for ExternalCarrierShippingProvider is generated using shipping method "Ground"
        Then the ExternalCarrier shipment request body should have
            | sku             | quantity | unit_price |
            | table-feet      | 4        | 6.67       |
            | table-corners   | 4        | 6.67       |
            | table-connector | 4        | 6.67       |
