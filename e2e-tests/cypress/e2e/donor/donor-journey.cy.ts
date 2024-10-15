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

  it("lets you identify with Passport", () => {
    const d = new Date();
    let year = d.getFullYear();

    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");
    cy.contains("Help with checking if passport is in date");
    cy.contains("Help with checking if passport is in date").click();
    cy.contains("Enter passport expiry date. For example 31 03 2012");
    cy.get("#passport-issued-day").type("01", {force: true});
    cy.get("#passport-issued-month").type("01", {force: true});
    cy.get("#passport-issued-year").type(year - 20, {force: true});
    cy.get(".govuk-button--secondary").contains("Check").click({force: true});
    cy.contains("Out of date");

    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");
    cy.contains("Help with checking if passport is in date");
    cy.contains("Help with checking if passport is in date").click();
    cy.contains("Enter passport expiry date. For example 31 03 2012");
    cy.get("#passport-issued-day").type("31", {force: true});
    cy.get("#passport-issued-month").type("10", {force: true});
    cy.get("#passport-issued-year").type(year, {force: true});
    cy.get(".govuk-button--secondary").contains("Check").click({force: true});
    cy.contains("In date");

    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");
    cy.contains("Passport").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which LPAs should this identity check apply to?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("UK passport");
    cy.contains("Where to find the passport number").click({force: true});
    cy.contains("The passport number is located at the top right-hand corner of the");

    // cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
    // cy.get(".govuk-button").contains("Continue").click();
    //
    // cy.contains("Initial identity confirmation complete");
    // cy.get(".govuk-button").contains("Continue").click();
    //
    // cy.contains("Select answer");
    //
    // cy.selectKBVAnswer({ correct: true });
    // cy.get(".govuk-button").contains("Continue").click();
    //
    // cy.selectKBVAnswer({ correct: true });
    // cy.get(".govuk-button").contains("Continue").click();
    //
    // cy.selectKBVAnswer({ correct: true });
    // cy.get(".govuk-button").contains("Continue").click();
    //
    // cy.contains(".moj-banner", "Identity check passed");
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
