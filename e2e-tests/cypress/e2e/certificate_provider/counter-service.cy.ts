describe("Counter service certificateProvider journey", () => {
  it("accepts a UK passport", () => {
    cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

    cy.contains("How will they prove their identity?");
    cy.contains("label", "Post Office").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which document will they take to the Post Office?");
    cy.contains("UK passport (up to 18 months expired)").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Does the name match the ID?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in the identity check");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("What is their date of birth?");
    cy.getInputByLabel("Day").type("20");
    cy.getInputByLabel("Month").type("01");
    cy.getInputByLabel("Year").type("1999");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Does the address on the ID document match the address in Sirius?");
    cy.contains("label", "Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Find a Post Office");
    cy.contains("St Neots").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Confirm Post Office route");
    cy.contains("Passport");
    cy.contains("St. Neots");
    cy.contains("Submission deadline");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("We will send you a letter.");
    cy.contains("Please do this by the deadline stated.");
  });

  it("accepts an international ID", () => {
    cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

    cy.contains("How will they prove their identity?");
    cy.contains("label", "Post Office").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which document will they take to the Post Office?");
    cy.contains("ID from another country").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Choose country");
    cy.getInputByLabel("Choose country").type("Austria");
    cy.contains("Austria").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Choose document");
    cy.contains("National ID").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Does the name match the ID?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in the identity check");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("What is their date of birth?");
    cy.getInputByLabel("Day").type("20");
    cy.getInputByLabel("Month").type("01");
    cy.getInputByLabel("Year").type("1999");
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
