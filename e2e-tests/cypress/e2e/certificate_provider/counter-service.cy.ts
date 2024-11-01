    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Does the address on the ID document match the address in Sirius?");
    cy.contains("label", "Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Find a Post Office");
    cy.contains("St Neots").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Confirm Post Office route");
    cy.contains("National ID (Austria)");
    cy.contains("St. Neots");
    cy.contains("Submission deadline");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("We will send you a letter.");
    cy.contains("Please do this by the deadline stated.");
  });
});
