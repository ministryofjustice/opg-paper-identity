describe("Identify a Donor", () => {
  it("lets you identify with National Insurance number", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

    cy.contains("How will you confirm your identity?");
    cy.get("label").contains("National insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");

    cy.contains("Edit the donor's details in Sirius").should('have.attr', 'href').and('include', 'lpa_details')
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in this identity check");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");

    cy.contains("Where to find the National Insurance number");
    cy.contains("Where to find the National Insurance number").click();
    cy.contains("Your National Insurance number can be found on");

    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Enter the National insurance number.");

    cy.getInputByLabel("National Insurance number").type("NP 11 22 33 C");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Identity document verified");
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

    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

    // Check form validation
    cy.contains("How will you confirm your identity?");
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("How will you confirm your identity?");
    cy.contains("Please select an option");

    // Check passport reveal
    cy.contains("Check if you're able to use an expired passport");
    // cy.screenshot();
    cy.contains("Check if you're able to use an expired passport").trigger("click");
    cy.contains("Check if you're able to use an expired passport").click();
    cy.contains("Enter passport expiry date. For example 31 03 2012").should('be.visible');

    // Check passport date validation fails
    cy.get("#passport-issued-day").should('be.visible').type("01", {force: true});
    cy.get("#passport-issued-month").type("01", {force: true});
    cy.get("#passport-issued-year").type(year - 20, {force: true});
    cy.get(".govuk-button--secondary").contains("Check").click({force: true});
    cy.contains("Out of date");

    // Check passport date validation succeeds
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");
    cy.contains("Check if you're able to use an expired passport");
    cy.contains("Check if you're able to use an expired passport").click();
    cy.contains("Enter passport expiry date. For example 31 03 2012").should('be.visible');
    cy.get("#passport-issued-day").type("31", {force: true});
    cy.get("#passport-issued-month").type("10", {force: true});
    cy.get("#passport-issued-year").type(year, {force: true});
    cy.get(".govuk-button--secondary").contains("Check").click({force: true});
    cy.contains("In date");

    // Check passport selection on form succeeds
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");
    cy.contains("Passport").click();
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("LPAs included in this identity check");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("UK passport");
    cy.contains("Where to find the passport number").click({force: true});
    cy.contains("The passport number is located at the top right-hand corner of the");

    cy.contains("Passport number").click();

    cy.get("#passport").type("123456781", {force: true});
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Please choose yes or no");

    cy.contains("No").click();
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("The passport needs to be no more than 18 months out of date");

    cy.get("#passport").clear();
    cy.contains("Yes").click();
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Enter the passport number.");

    cy.contains("Help with checking if passport is in date");
    cy.contains("Help with checking if passport is in date").click();
    cy.contains("Enter passport expiry date. For example, 31 03 2012");

    cy.get("#passport").type("123456781", {force: true});
    cy.get(".govuk-radios__label").contains("Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Identity document verified");
  });

  it("lets you identify with Driving licence", () => {

    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");
    cy.contains("UK driving licence (must be current)").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in this identity check");
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
    cy.contains("Enter the Driving licence number.");

    cy.get("#dln").type("MORGA657054SM9IJ", {force: true});
    cy.get(".govuk-radios__label").contains("Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Identity document verified");
  });

  it("fails if you get KBVs wrong", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

    cy.contains("How will you confirm your identity?");
    cy.get("label").contains("National insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in this identity check");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");
    cy.getInputByLabel("National Insurance number").type("NP 11 22 33 C");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Identity document verified");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Select answer");

    cy.selectKBVAnswer({ correct: false });
    cy.get(".govuk-button").contains("Continue").click();

    cy.selectKBVAnswer({ correct: false });
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains(".moj-banner", "Identity check was not successful.");
  });

  it("passes on STOP or REFER if you get four out of four KBVs right", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G0");

    cy.contains("How will you confirm your identity?");
    cy.get("label").contains("National insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in this identity check");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");
    cy.getInputByLabel("National Insurance number").type("NP 11 22 33 C");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Identity document verified");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Select answer");

    cy.selectKBVAnswer({ correct: true });
    cy.get(".govuk-button").contains("Continue").click();

    cy.selectKBVAnswer({ correct: true });
    cy.get(".govuk-button").contains("Continue").click();

    cy.selectKBVAnswer({ correct: true });
    cy.get(".govuk-button").contains("Continue").click();

    cy.selectKBVAnswer({ correct: true });
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains(".moj-banner", "Identity check passed");
  });

  it("fails on STOP or REFER if you get any KBV wrong", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35F0");

    cy.contains("How will you confirm your identity?");
    cy.get("label").contains("National insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in this identity check");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");
    cy.getInputByLabel("National Insurance number").type("NP 11 22 33 C");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Identity document verified");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Select answer");

    cy.selectKBVAnswer({ correct: false });
    cy.get(".govuk-button").contains("Continue").click();

    cy.selectKBVAnswer({ correct: true });
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains(".moj-banner", "Identity check was not successful.");
  });

  it("handles 2 LPAs", () => {
    cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3&lpas[]=M-XYXY-YAGB-35G3");

    cy.contains("How will you confirm your identity?");
    cy.get("label").contains("National insurance number").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Do the details match the ID document?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in this identity check");
    cy.contains("Remove").click();

    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Remove").should('not.exist');
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("National insurance number");
  });

});
