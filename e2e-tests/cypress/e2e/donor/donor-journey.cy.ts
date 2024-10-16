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

    cy.contains("Where to find the National Insurance number");
    cy.contains("Where to find the National Insurance number").click();
    cy.contains("Your National Insurance number can be found on");

    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Value is required and can't be empty");

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

    // Check form validation
    cy.contains("How will they confirm their identity?");
    cy.get(".govuk-button").contains("Continue").click();
    cy.screenshot();
    cy.contains("How will they confirm their identity?");
    cy.contains("Please select an option");
    cy.screenshot();

    // Check passport reveal
    cy.contains("Help with checking if passport is in date");
    // cy.screenshot();
    cy.contains("Help with checking if passport is in date").trigger("click");
    cy.contains("Help with checking if passport is in date").click();
    cy.contains("Enter passport expiry date. For example 31 03 2012").should('be.visible');

    // Check passport date validation fails
    cy.get("#passport-issued-day").should('be.visible').type("01", {force: true});
    cy.get("#passport-issued-month").type("01", {force: true});
    cy.get("#passport-issued-year").type(year - 20, {force: true});
    cy.get(".govuk-button--secondary").contains("Check").click({force: true});
    cy.contains("Out of date");

    // Check passport date validation succeeds
    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");
    cy.contains("Help with checking if passport is in date");
    cy.contains("Help with checking if passport is in date").click();
    cy.contains("Enter passport expiry date. For example 31 03 2012").should('be.visible');
    cy.get("#passport-issued-day").type("31", {force: true});
    cy.get("#passport-issued-month").type("10", {force: true});
    cy.get("#passport-issued-year").type(year, {force: true});
    cy.get(".govuk-button--secondary").contains("Check").click({force: true});
    cy.contains("In date");

    // Check passport selection on form succeeds
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

    cy.contains("Passport number").click();

    cy.get("#passport").type("123456781", {force: true});
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Value is required and can't be empty");

    cy.contains("No").click();
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("The passport needs to be no more than 18 months out of date");

    cy.get("#passport").clear();
    cy.contains("Yes").click();
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Value is required and can't be empty");

    cy.contains("Help with checking if passport is in date");
    cy.contains("Help with checking if passport is in date").click();
    cy.contains("Enter passport expiry date. For example, 31 03 2012");

    cy.get("#passport").type("123456781", {force: true});
    cy.contains("Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Initial identity confirmation complete");
  });

  it("lets you identify with Driving licence", () => {

    cy.visit("/start?personType=donor&lpas[]=M-1234-1234-1234");
    cy.contains("Driving licence").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which LPAs should this identity check apply to?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("UK Driving Licence");
    cy.contains("Where to find the driving licence number").click({force: true});
    cy.contains("The driving licence number is found in section 5 of the details section for both paper and photo ID licences");

    cy.contains("Driving licence number").click();

    cy.get("#dln").type("MORGA657054SM9IJ", {force: true});
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Please choose yes or no");

    cy.contains("No").click();
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("The driving licence needs to be in date. Check the expiry date and change to Yes, or try a different method");

    cy.get("#dln").clear();
    cy.contains("Yes").click();
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Value is required and can't be empty");

    cy.get("#dln").type("MORGA657054SM9IJ", {force: true});
    cy.contains("Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Initial identity confirmation complete");
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
