import { Given, Then, When } from "@badeball/cypress-cucumber-preprocessor";

const PACKIYO_URL = Cypress.env('CYPRESS_HOST_FRONTEND_PACKIYO');
const PACKIYO_URL_REMIX = Cypress.env('CYPRESS_HOST_FRONTEND_PACKIYO_REMIX');
const USER_EMAIL = Cypress.env('CYPRESS_USER_EMAIL');
const USER_PASSWORD = Cypress.env('CYPRESS_USER_PASSWORD');

Given("I am on automation page", () => {
  cy.viewport(1920, 1080)
    cy.clearCookies();
    cy.visit(`${PACKIYO_URL}/login`);
    cy.get("[type='email']").type(USER_EMAIL);
    cy.get("[type='password']").type(USER_PASSWORD);
    cy.get('button[type=submit]').click();
    cy.visit(`${PACKIYO_URL}`);
    cy.visit(`${PACKIYO_URL_REMIX}/app/settings/login`);
});

When("I click on the first automation present in status cel", () => {
  cy.wait(2000);
  cy.get('a').each(($el) => {
      const href = $el.attr('href');

      if(href === "/app/automations") {
        cy.visit(`${PACKIYO_URL_REMIX}/app/automations`);
      }
  });
  cy.get('.table tr:nth-child(1) .toggle-switch label').click();
});

Then("I should see the opposite case to the present one", () => {
  const checkboxSelector = '.table tr:nth-child(1) .toggle-switch input';

  cy.get(checkboxSelector).then($checkbox => {
    const initialState = $checkbox.is(':checked');

    cy.get('button[type=submit]').click();

    cy.get(checkboxSelector).should($checkboxAfter => {
      const finalState = $checkboxAfter.is(':checked');
      expect(finalState).to.not.equal(initialState);
    });
  });
});

