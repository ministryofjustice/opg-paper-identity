/// <reference types="cypress" />

declare namespace Cypress {
  interface Chainable {
    /**
     * Get an input element by its label text
     */
    getInputByLabel(string): Chainable<any>;
  }
}
