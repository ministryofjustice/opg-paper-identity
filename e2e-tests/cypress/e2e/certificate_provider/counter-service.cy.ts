describe("Counter service certificateProvider journey", () => {
  it("accepts a UK passport", () => {
    cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

    cy.contains("How will you confirm your identity?");
    cy.get("input#POST_OFFICE").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which document will they take to the Post Office?");
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("There is a problem");
    cy.contains("Please select an option");

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

    cy.contains("Post Office letter");
    cy.contains("label", "Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.get('.govuk-heading-xl').contains("Post Office branch finder");
    cy.get('input[name=postoffice]').first().click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.get('.govuk-heading-xl').contains("Confirm Post Office route");
    cy.get('dd#address').contains("18 BOURNE COURT");
    cy.get('span#lpaId').contains("M-XYXY-YAGA-0000");
    cy.get('dd#displayIdMethod').contains("UK Passport (current or expired in the last 18 months)");
    cy.get('span#poAddressLine').contains("St. Neots");
    cy.contains("Submission deadline");
    cy.get(".govuk-button").contains("Confirm and send letter").click();

    cy.contains("We will send you a letter to take to the Post Office with your chosen identity document.");
    cy.contains("If you haven't already, please return your signed certificate.");
  });

  it("accepts an international ID", () => {
    cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

    cy.contains("How will you confirm your identity?");
    cy.get("input#POST_OFFICE").click();
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("Which document will they take to the Post Office?");
    cy.contains("ID from another country").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Choose country");
    cy.get("input#idCountry").type("Austria");
    cy.contains("Austria").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Choose ID document for Austria");
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

    cy.contains(" Post Office letter");
    cy.contains("label", "Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.get('.govuk-heading-xl').contains("Post Office branch finder");
    cy.get('input[name=postoffice]').first().click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.get('.govuk-heading-xl').contains("Confirm Post Office route");
    cy.get('dd#address').contains("18 BOURNE COURT");
    cy.get('span#lpaId').contains("M-XYXY-YAGA-0000");
    cy.get('dd#displayIdMethod').contains("National ID (Austria)");
    cy.get('span#poAddressLine').contains("St. Neots");
    cy.contains("Submission deadline");
    cy.get(".govuk-button").contains("Confirm and send letter").click();
  });

  it("handles 2 LPAs", () => {
    cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-35G3&lpas[]=M-XYXY-YAGB-35G3");

    cy.contains("How will you confirm your identity?");
    cy.get("input#POST_OFFICE").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which document will they take to the Post Office?");
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("There is a problem");
    cy.contains("Please select an option");

    cy.contains("UK photocard driving licence (must be current)").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Does the name match the ID?");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("LPAs included in the identity check");
    cy.get('.govuk-link').contains('Remove');
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("What is their date of birth?");
    cy.getInputByLabel("Day").type("20");
    cy.getInputByLabel("Month").type("01");
    cy.getInputByLabel("Year").type("1999");
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Does the address on the driving licence match the address in Sirius?");
    cy.contains("label", "Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.get('.govuk-heading-xl').contains("Post Office branch finder");
    cy.get('input[name=postoffice]').first().click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.get('.govuk-heading-xl').contains("Confirm Post Office route");
    cy.get('span#lpaId').contains("M-XYXY-YAGA-35G3");
    cy.get('span#lpaId').contains("M-XYXY-YAGB-35G3");
    cy.get('dd#displayIdMethod').contains("UK driving licence (must be current)");
    cy.get('span#poAddressLine').contains("St. Neots");
    cy.contains("Submission deadline");
    cy.get(".govuk-button").contains("Confirm and send letter").click();

    cy.contains("We will send you a letter to take to the Post Office with your chosen identity document.");
    cy.contains("If you haven't already, please return your signed certificate.");
  });

  it("allows you to search for a different postoffice", () => {
    cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

    cy.contains("How will you confirm your identity?");
    cy.get("input#POST_OFFICE").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.contains("Which document will they take to the Post Office?");
    cy.get(".govuk-button").contains("Continue").click();
    cy.contains("There is a problem");
    cy.contains("Please select an option");

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

    cy.contains("Post Office letter");
    cy.contains("label", "Yes").click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.get('.govuk-heading-xl').contains("Post Office branch finder");
    cy.get('input[name=searchString]').clear();
    cy.get('input[name=searchString]').type('SW1A 1AA');
    cy.get("input[value='Search for a Post Office']").click();
    cy.get('input[name=postoffice]').last().click();
    cy.get(".govuk-button").contains("Continue").click();

    cy.get('.govuk-heading-xl').contains("Confirm Post Office route");
    cy.get('dd#address').contains("18 BOURNE COURT");
    cy.get('span#lpaId').contains("M-XYXY-YAGA-0000");
    cy.get('dd#displayIdMethod').contains("UK Passport (current or expired in the last 18 months)");
    cy.get('span#poAddressLine').contains("6 Raphael Street");
    cy.contains("Submission deadline");
    cy.get(".govuk-button").contains("Confirm and send letter").click();

    cy.contains("We will send you a letter to take to the Post Office with your chosen identity document.");
    cy.contains("If you haven't already, please return your signed certificate.");
  });
});
