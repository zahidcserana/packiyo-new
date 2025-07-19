@reports @inventory_snapshot
Feature: Inventory Snapshot Report
    As a warehouse manager
    I want to generate a report of the current inventory snapshot
    So that I can have a clear view of the current stock levels on a given date

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the instance has the feature flag "App\Features\InventorySnapshot" enabled
        And a member user "roger+test_3pl@packiyo.com" named "Roger" based in "United States"
        And the user "roger+test_3pl@packiyo.com" belongs to the customer "Test 3PL"
        And the user "roger+test_3pl@packiyo.com" is authenticated
        And the customer "Test 3PL" has a warehouse named "Test Warehouse Primary" in "United States"
        And the warehouse "Test Warehouse Primary" has a pickable location called "PA-1"
        And the warehouse "Test Warehouse Primary" has a pickable location called "PB-1"
        And the warehouse "Test Warehouse Primary" has a pickable location called "PC-1"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse Secondary" in "United States"
        And the warehouse "Test Warehouse Secondary" has a pickable location called "SA-1"
        And the warehouse "Test Warehouse Secondary" has a pickable location called "SB-1"
        And the warehouse "Test Warehouse Secondary" has a pickable location called "SC-1"
        And a customer called "Test 3PL Client 1" based in "United States" client of 3PL "Test 3PL"
        And a customer called "Test 3PL Client 2" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client 1" has an SKU "product-blue" named "Product Blue" priced at 1.5
        And the customer "Test 3PL Client 2" has an SKU "product-red" named "Product Red" priced at 2.0

    Scenario: All customers, all warehouses, each client occupying one location in each warehouse
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Secondary | SA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PA-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Secondary | SA-1          | product-red  | Product Red  | 20               |

    Scenario: All customers, all warehouses, each client occupying multiple locations in each warehouse
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary   | PB-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Secondary | SA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PA-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PB-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Secondary | SA-1          | product-red  | Product Red  | 20               |

    Scenario: All customers, all warehouses, date without any inventory
        Given the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        When I generate the report
        Then the report should contain no rows

    Scenario: One customer, all warehouses, each client occupying multiple locations in each warehouse
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer          | Warehouse |
            | 2024-01-01 | Test 3PL Client 1 | All       |
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary   | PB-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Secondary | SA-1          | product-blue | Product Blue | 10               |

    Scenario: One customer, one warehouses, each client occupying multiple locations in each warehouse
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer          | Warehouse              |
            | 2024-01-01 | Test 3PL Client 1 | Test Warehouse Primary |
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse              | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary | PB-1          | product-blue | Product Blue | 10               |

    Scenario: All customer, one warehouses, each client occupying multiple locations in each warehouse
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse              |
            | 2024-01-01 | All      | Test Warehouse Primary |
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse              | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary | PB-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 2 | Test Warehouse Primary | PA-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Primary | PB-1          | product-red  | Product Red  | 20               |

    Scenario: 3PL child trying to report for all customers
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL Client 1"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        When I generate the report
        Then the report should've thrown an exception

    Scenario: 3PL child trying to report for himself
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL Client 1"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Warehouse |
            | 2024-01-01 | All       |
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary   | PB-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Secondary | SA-1          | product-blue | Product Blue | 10               |

    Scenario: 3PL child trying to report for himself for a specific warehouse
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL Client 1"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Warehouse |
            | 2024-01-01 | Test Warehouse Primary |
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary   | PB-1          | product-blue | Product Blue | 10               |

    Scenario: All customers, all warehouses, each client occupying multiple locations in each warehouse, default sort is product name asc
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary   | PB-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Secondary | SA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PA-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PB-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Secondary | SA-1          | product-red  | Product Red  | 20               |

    Scenario: All customers, all warehouses, each client occupying multiple locations in each warehouse, searching for Product
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        And the search term is "Product"
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary   | PB-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Secondary | SA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PA-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PB-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Secondary | SA-1          | product-red  | Product Red  | 20               |

    Scenario: All customers, all warehouses, each client occupying multiple locations in each warehouse, searching for Product Red
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        And the search term is "Product Red"
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 2 | Test Warehouse Primary   | PA-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PB-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Secondary | SA-1          | product-red  | Product Red  | 20               |



    Scenario: All customers, all warehouses, each client occupying multiple locations in each warehouse, sorted asc by warehouse
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        And the report is sorted by the column "Warehouse" in "asc" direction
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary   | PB-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PA-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PB-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 1 | Test Warehouse Secondary | SA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 2 | Test Warehouse Secondary | SA-1          | product-red  | Product Red  | 20               |

    Scenario: All customers, all warehouses, each client occupying multiple locations in each warehouse, sorted asc by customer
        Given the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 1" had 10 units of "product-blue" in "SA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PA-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "PB-1" on "2024-01-01"
        And the customer "Test 3PL Client 2" had 20 units of "product-red" in "SA-1" on "2024-01-01"
        And the session customer is set to "Test 3PL"
        And the report is "App\Reports\InventorySnapshotReport"
        And these are the report filters
            | Date       | Customer | Warehouse |
            | 2024-01-01 | All      | All       |
        And the report is sorted by the column "Customer" in "asc" direction
        When I generate the report
        Then the report should contain the following rows in the correct order:
            | customer          | warehouse                | location_name | sku          | name         | quantity_on_hand |
            | Test 3PL Client 1 | Test Warehouse Primary   | PA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Primary   | PB-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 1 | Test Warehouse Secondary | SA-1          | product-blue | Product Blue | 10               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PA-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Primary   | PB-1          | product-red  | Product Red  | 20               |
            | Test 3PL Client 2 | Test Warehouse Secondary | SA-1          | product-red  | Product Red  | 20               |





