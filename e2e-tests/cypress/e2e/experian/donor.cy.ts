describe("Identify a Donor with fraud check", () => {
  it("passes fraud check", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

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
  });

  it("reaches no decision result on insufficient ID data", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G4");

    cy.contains("How will they confirm their identity?");
    cy.contains("Lee Nodec");
    cy.contains("National Insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which LPAs should this identity check apply to?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");

    cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Not enough details to continue with this form of identification");

    cy.get(".govuk-button").contains("Try a different method").click();
    cy.contains("How will they confirm their identity?");
  });

  it("reaches a stop result on high fraud risk", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G0");

    cy.contains("How will they confirm their identity?");
    cy.contains("Lee Nohope");
    cy.contains("National Insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which LPAs should this identity check apply to?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");

    cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Stop response received from fraud check service.");
  });

  it("reaches no decision result on insufficient KBV data", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G5");

    cy.contains("How will they confirm their identity?");
    cy.contains("Lee Thinfile");
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

    cy.contains("Not enough details to continue with this form of identification");
  });
});
