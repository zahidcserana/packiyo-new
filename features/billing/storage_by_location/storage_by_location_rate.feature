@3pl @billing @storage
Feature: Billing for storage by location over different periods
    As the owner of a 3PL business
    I want to be able to charge storage rates by location types
    So that I can charge my customers for the locations they occupied.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And the customer "Test 3PL" has a warehouse named "Test Warehouse" in "United States"
        And the warehouse "Test Warehouse" has 10 locations of type "Bin"
        And the warehouse "Test Warehouse" has 10 locations of type "Shelve"
        And the warehouse "Test Warehouse" has 10 locations of type "Pallet"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the customer "Test 3PL Client" has an SKU "test-product-blue" named "Test Product Blue" priced at 3.99
        And the customer "Test 3PL Client" has an SKU "test-product-red" named "Test Product Red" priced at 5.99
        And the customer "Test 3PL Client" has an SKU "test-product-yellow" named "Test Product Yellow" priced at 8.49
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a storage by location rate "Test Storage Rate" on rate card "Test Rate Card" with fee 1.5

    Scenario: Billing a default storage by location rate with no occupied locations within the period
        Given the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by "month"
        And the warehouse "Test Warehouse" had 1 SKU "test-product-blue" in location "Bin-0001" from "2023-01-01" to "2023-01-31"
        When I calculate an invoice for customer "Test 3PL Client" for the period "2023-05-01" to "2023-05-31"
        And the invoice is calculated in the background
        Then the invoice should not have any invoice items

    Scenario Outline: Billing a default storage by location rate with one occupied location within the period
        Given the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by <billed by>
        And the warehouse "Test Warehouse" had <quantity> SKU "test-product-blue" in location "Bin-0001" from "<stored from>" to "<stored to>"
        When I calculate an invoice for customer "Test 3PL Client" for the period "<period start>" to "<period end>"
        And the invoice is calculated in the background
        Then the invoice should have <item count> invoice items

        Examples:
            | billed by | quantity | stored from | stored to  | period start | period end | item count |
            | day       | 1        | 2023-05-01  | 2023-05-31 | 2023-05-01   | 2023-05-31 | 31         |
            | week      | 1        | 2023-05-01  | 2023-05-31 | 2023-05-01   | 2023-05-31 | 4          |
            | month     | 1        | 2023-05-01  | 2023-05-31 | 2023-05-01   | 2023-05-31 | 1          |
            | day       | 1        | 2023-05-01  | 2023-05-15 | 2023-05-01   | 2023-05-31 | 15         |
            | week      | 1        | 2023-05-01  | 2023-05-15 | 2023-05-01   | 2023-05-31 | 3          |
            | month     | 1        | 2023-05-01  | 2023-05-15 | 2023-05-01   | 2023-05-31 | 1          |
            | day       | 1        | 2023-05-01  | 2023-05-07 | 2023-05-01   | 2023-05-31 | 7          |
            | week      | 1        | 2023-05-01  | 2023-05-07 | 2023-05-01   | 2023-05-31 | 1          |
            | month     | 1        | 2023-05-01  | 2023-05-07 | 2023-05-01   | 2023-05-31 | 1          |
            | month     | 1        | 2023-05-01  | 2023-05-31 | 2023-05-01   | 2023-05-30 | 0          |
            | month     | 1        | 2024-06-01  | 2024-06-02 | 2024-06-29   | 2024-06-30 | 1          |
            | month     | 1        | 2024-06-01  | 2024-06-02 | 2024-06-29   | 2024-07-01 | 1          |
            | month     | 1        | 2024-06-01  | 2024-06-02 | 2024-06-29   | 2024-07-31 | 1          |
            | month     | 1        | 2024-06-01  | 2024-07-01 | 2024-06-29   | 2024-07-31 | 2          |

    Scenario Outline: Billing a default storage by location rate with one occupied location within the period with two products
        Given the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by <billed by>
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" <status>
        And the warehouse "Test Warehouse" had <quantity> SKU "test-product-blue" in location "Bin-0001" from "<stored from>" to "<stored to>"
        And we log all occupied locations in the warehouse "Test Warehouse" for the customer "Test 3PL Client" from "<period start>" to "<period end>"
        When I calculate an invoice for customer "Test 3PL Client" for the period "<period start>" to "<period end>"
        And the invoice is calculated in the background
        Then the invoice should have <item count> invoice items

        Examples:
            | billed by | quantity | stored from | stored to  | period start | period end | item count | status |
            | day       | 1        | 2023-05-01  | 2023-05-31 | 2023-05-01   | 2023-05-31 | 31         | off    |
            | week      | 1        | 2023-05-01  | 2023-05-28 | 2023-05-01   | 2023-05-31 | 4          | off    |
            | month     | 1        | 2023-05-01  | 2023-05-31 | 2023-05-01   | 2023-05-31 | 1          | off    |
            | day       | 1        | 2023-05-01  | 2023-05-15 | 2023-05-01   | 2023-05-31 | 15         | off    |
            | week      | 1        | 2023-05-01  | 2023-05-15 | 2023-05-01   | 2023-05-31 | 3          | off    |
            | month     | 1        | 2023-05-01  | 2023-05-15 | 2023-05-01   | 2023-05-31 | 1          | off    |
            | day       | 1        | 2023-05-01  | 2023-05-07 | 2023-05-01   | 2023-05-31 | 7          | off    |
            | week      | 1        | 2023-05-01  | 2023-05-07 | 2023-05-01   | 2023-05-31 | 1          | off    |
            | month     | 1        | 2023-05-01  | 2023-05-07 | 2023-05-01   | 2023-05-31 | 1          | off    |

    Scenario Outline: Billing a default storage by location rate without DocumentDb service working
        Given the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by <billed by>
        And the customer "Test 3PL" has the feature flag "App\Features\Wallet" <status>
        But the DocumentDb service is not working
        And the warehouse "Test Warehouse" had <quantity> SKU "test-product-blue" in location "Bin-0001" from "<stored from>" to "<stored to>"
        And the warehouse "Test Warehouse" had <quantity> SKU "test-product-red" in location "Bin-0001" from "<stored from>" to "<stored to>"
        When I calculate an invoice for customer "Test 3PL Client" for the period "<period start>" to "<period end>"
        And the invoice is calculated in the background
        Then the invoice should have <item count> invoice items
        And it should have used <calculator> data for the calculation

        Examples:
            | billed by | quantity | stored from | stored to  | period start | period end | item count | status | calculator |
            | day       | 1        | 2023-05-01  | 2023-05-31 | 2023-05-01   | 2023-05-31 | 31         | on     | MySql      |
            | day       | 1        | 2023-05-01  | 2023-05-31 | 2023-05-01   | 2023-05-31 | 31         | off    | MySql      |

    Scenario Outline: Billing a default storage by location rate with one occupied locations within the period, product without location
        Given the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by <billed by>
        And the warehouse "Test Warehouse" had <quantity> SKU "test-product-blue" in location "Bin-0001" from "<stored from>" to "<stored to>"
        And the warehouse "Test Warehouse" had <quantity> SKU "test-product-red" in location "Bin-0001" from "<stored from>" to "<stored to>"
        And the SKU "test-product-blue" is not assigned to the location "Bin-0001"
        And the SKU "test-product-red" is not assigned to the location "Bin-0001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "<period start>" to "<period end>"
        And the invoice is calculated in the background
        Then the invoice should have <item count> invoice items

        Examples:
            | billed by | quantity | stored from | stored to  | item count | period start | period end |
            | day       | 1        | 2023-05-01  | 2023-05-31 | 31         | 2023-05-01   | 2023-05-31 |
            | week      | 1        | 2023-05-01  | 2023-05-31 | 4          | 2023-05-01   | 2023-05-31 |
            | month     | 1        | 2023-05-01  | 2023-05-31 | 1          | 2023-05-01   | 2023-05-31 |

    Scenario Outline: Billing a default storage by location rate with one occupied locations outside the period, product without location
        Given the storage by location rate "Test Storage Rate" applies to all location types
        And the storage by location rate "Test Storage Rate" invoices by <billed by>
        And the warehouse "Test Warehouse" had <quantity> SKU "test-product-blue" in location "Bin-0001" from "<stored from>" to "<stored to>"
        And the warehouse "Test Warehouse" had <quantity> SKU "test-product-red" in location "Bin-0001" from "<stored from>" to "<stored to>"
        And the SKU "test-product-blue" is not assigned to the location "Bin-0001"
        And the SKU "test-product-red" is not assigned to the location "Bin-0001"
        When I calculate an invoice for customer "Test 3PL Client" for the period "<period start>" to "<period end>"
        And the invoice is calculated in the background
        Then the invoice should have <item count> invoice items

        Examples:
            | billed by | quantity | stored from | stored to  | item count | period start | period end |
            | day       | 1        | 2023-05-01  | 2023-07-01 | 30         | 2023-06-01   | 2023-06-30 |
            | week      | 1        | 2023-05-01  | 2023-07-01 | 4          | 2023-06-01   | 2023-06-30 |
            | month     | 1        | 2023-05-01  | 2023-07-01 | 1          | 2023-06-01   | 2023-06-30 |
