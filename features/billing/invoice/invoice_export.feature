# todo: figure out the failing test and remove @skip-ci
@3pl @billing @export @skip-ci
Feature: Exporting an invoice to CSV
    As the owner of a 3PL business
    I want to export clients' invoices as spreadsheets
    So that I can work with the data before billing them.

    Background:
        Given a 3PL called "Test 3PL" based in "United States"
        And a member user "roger@packiyo.com" named "Roger" based in "United States"
        And the user "roger@packiyo.com" belongs to the customer "Test 3PL"
        And a customer called "Test 3PL Client" based in "United States" client of 3PL "Test 3PL"
        And the 3PL "Test 3PL" has a rate card "Test Rate Card" assigned to its client "Test 3PL Client"
        And a picking rate "Test Picking Rate" on rate card "Test Rate Card"
        And An invoice was calculated for client "Test 3PL Client" for the period "2023-06-29" to "2023-07-28"
        And the latest invoice for client "Test 3PL Client" was finalized

    Scenario: Exporting an invoice with no line items
        When I export the latest invoice for customer "Test 3PL Client"
        And the invoice is exported in the background
        Then the invoice is exported to a file named "Test 3PL Client 2023-06-29 - 2023-07-28.csv"
        And the first exported line contains the "Type (charge/invoice item)" as "shipments_by_picking_rate_v2"
        And the first exported line contains the "Name (charge/invoice item)" as "Test Picking Rate"
        And the first exported line contains the "Description (charge/invoice item)" as "Order: 123456789, TN: TN-123456789 | test"
        And the first exported line contains the "Quantity (charge/invoice item)" as "1.00"
        And the first exported line contains the "Unit Price (charge/invoice item)" as "1.00"
        And the first exported line contains the "Total Price (charge/invoice item)" as "1.00"
        And the first exported line contains the "Customer" as "Test 3PL Client"
        And the first exported line contains the "Invoice id" as the invoice's db ID
        And the first exported line contains the "Invoice Period Start" as "2023-06-29"
        And the first exported line contains the "Invoice Period End" as "2023-07-28"
        And the first exported line contains the "Client Name" as "Test 3PL Client"
        And the first exported line contains the "Order No" as "123456789"
        And the first exported line contains the "Tracking No" as "TN-123456789"
