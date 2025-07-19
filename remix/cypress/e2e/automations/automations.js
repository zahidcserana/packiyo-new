import { Given, Then, When } from "@badeball/cypress-cucumber-preprocessor";

const PACKIYO_URL = Cypress.env('CYPRESS_HOST_FRONTEND_PACKIYO');
const PACKIYO_URL_REMIX = Cypress.env('CYPRESS_HOST_FRONTEND_PACKIYO_REMIX');
const USER_EMAIL = Cypress.env('CYPRESS_USER_EMAIL');
const USER_PASSWORD = Cypress.env('CYPRESS_USER_PASSWORD');

Given("I am on settings page", () => {
  cy.viewport(1920, 1080)
    cy.clearCookies();
    cy.visit(`${PACKIYO_URL}/login`);
    cy.get("[type='email']").type(USER_EMAIL);
    cy.get("[type='password']").type(USER_PASSWORD);
    cy.get('button[type=submit]').click();
    cy.visit(`${PACKIYO_URL}`);
    cy.visit(`${PACKIYO_URL_REMIX}/app/settings/login`);
});

When("I click on automations link", () => {
  cy.wait(2000);
  cy.get('a').each(($el) => {
    const href = $el.attr('href');

    if(href === "/app/automations") {
        cy.get($el).click();
    }
  });
});

Then("I should be redirected to automations page", () => {
  cy.location("pathname").should((path) => {
      expect(path).equal("/app/automations");
  })
});

