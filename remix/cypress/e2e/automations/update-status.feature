Feature: Automation Update status

  Scenario: Update automations status
    Given I am on automation page
    When I click on the first automation present in status cel
    Then I should see the opposite case to the present one