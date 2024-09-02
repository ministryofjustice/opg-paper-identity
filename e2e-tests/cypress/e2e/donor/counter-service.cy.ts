describe("Counter service donor journey", () => {
  it("accepts a UK passport", () => {
    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");

    cy.contains("How will they confirm their identity?");
    cy.contains("label", "Post Office").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which document will they take to the Post office?");
    cy.contains("UK passport").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which LPAs should this identity check apply to?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Find a Post Office");
    cy.contains("St Neots").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Confirm Post Office route");
    cy.contains("UK passport");
    cy.contains("St. Neots");
    cy.contains("Submission deadline");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("We will send you a letter.");
    cy.contains("Please do this by the deadline stated.");
  });

  it("accepts an international ID", () => {
    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");

    cy.contains("How will they confirm their identity?");
    cy.contains("label", "Post Office").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which document will they take to the Post office?");
    cy.contains("ID from another country").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Choose country");
    cy.getInputByLabel("Choose country").type("Austria");
    cy.contains("Austria").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Choose document");
    cy.contains("National ID").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which LPAs should this identity check apply to?");
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
