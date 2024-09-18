describe("Identify a Donor", () => {
  it("lets you identify with National Insurance number", () => {
    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");

    cy.contains("How will they confirm their identity?");
    cy.contains("National Insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which LPAs should this identity check apply to?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");
    cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Initial identity confirmation complete");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Select answer");

    cy.selectKBVAnswer({ correct: true });
    cy.get(".govuk-button").contains("Continue").click();

    cy.selectKBVAnswer({ correct: true });
    cy.get(".govuk-button").contains("Continue").click();

    cy.selectKBVAnswer({ correct: true });
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains(".moj-banner", "Identity check passed");
  });

  it("fails if you get KBVs wrong", () => {
    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");

    cy.contains("How will they confirm their identity?");
    cy.contains("National Insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which LPAs should this identity check apply to?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");
    cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Initial identity confirmation complete");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Select answer");

    cy.selectKBVAnswer({ correct: false });
    cy.get(".govuk-button").contains("Continue").click();

    cy.selectKBVAnswer({ correct: false });
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains(".moj-banner", "Identity check failed");
  });
});
