/// <reference types="cypress" />

declare namespace Cypress {
  interface Chainable {
    /**
     * Get an input element by its label text
     */
    getInputByLabel(string): Chainable<any>;

    /**
     *
     * @param string
     */
    selectKBVAnswer({ correct: boolean }): Chainable<any>;
  }
}
