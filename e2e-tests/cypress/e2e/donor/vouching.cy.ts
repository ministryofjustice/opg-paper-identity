describe("Vouching donor journey", () => {
  it("vouching route allows return to start", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

    cy.contains("How will they confirm their identity?");
    cy.contains("Other methods").click({"force": true});
    cy.contains("label", "Choose someone to prove their identity on donor's behalf").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("What is Vouching?");
    cy.contains("label", "No, choose a different method").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("How will they confirm their identity?");
  });

  it("vouching route completes", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

    cy.contains("How will they confirm their identity?");
    cy.contains("Other methods").click({"force": true});
    cy.contains("label", "Choose someone to prove their identity on donor's behalf").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("What is Vouching?");
    cy.contains("label", "Yes, send the letter about vouching to the donor").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("What happens next?");
    cy.get(".govuk-button").contains("Finish and return to Sirius");
  });
});
