describe("Identify a Certificate Provider", () => {
    it("lets you identify with National Insurance number", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the name match the ID?");
        cy.contains("Edit the certificate provider's details in Sirius").should('have.attr', 'href').and('include', 'lpa-details')
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("LPAs included in the identity check");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("What is their date of birth?");
        cy.getInputByLabel("Day").type("20");
        cy.getInputByLabel("Month").type("01");
        cy.getInputByLabel("Year").type("1999");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the address on the ID document match the address in Sirius?");
        cy.contains("Yes").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("National insurance number");
        cy.contains("20 January 1999");
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

        cy.contains(".moj-alert", "Identity check passed");
    });

    it("Passport - throws out of date error", () => {
        const d = new Date();
        let year = d.getFullYear();

        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        // Check form validation
        cy.contains("How will you confirm your identity?");
        cy.get(".govuk-button").contains("Continue").click();
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
    });

    it("Passport - date validation succeeds", () => {
        const d = new Date();
        let year = d.getFullYear();
        // Check passport date validation succeeds
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");
        cy.contains("Check if you're able to use an expired passport");
        cy.contains("Check if you're able to use an expired passport").click();
        cy.contains("Enter passport expiry date. For example 31 03 2012").should('be.visible');
        cy.get("#passport-issued-day").type("31", {force: true});
        cy.get("#passport-issued-month").type("10", {force: true});
        cy.get("#passport-issued-year").type(year, {force: true});
        cy.get(".govuk-button--secondary").contains("Check").click({force: true});
        cy.contains("In date");

        cy.contains("Identity documents accepted at the Post Office");
        cy.contains("Identity documents accepted at the Post Office").click();
        cy.contains("UK passport (up to 18 months expired)").should('be.visible');
    });
    it("Passport - succeeds on correct form selections", () => {

        const d = new Date();
        let year = d.getFullYear();
        // Check passport selection on form succeeds
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");
        cy.contains("Passport").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("Does the name match the ID?");
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("LPAs included in the identity check");

        cy.get(".govuk-button").contains("Add another LPA").click();
        cy.contains("Find an LPA to add");
        cy.get("#lpa").should('be.visible').type("M-0000-0000-000");        // bad LPA number format
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.contains("Not a valid LPA number. Enter an LPA number to continue.");

        cy.get(".govuk-button").contains("Return to LPA list").click();
        cy.get(".govuk-button").contains("Add another LPA").click();
        cy.get("#lpa").should('be.visible').type("M-DRAF-TLPA-CPG3");        // draft LPA
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.contains("This LPA cannot be added as it’s status is set to Draft. LPAs need to be in the In progress status to be added to this ID check.");

        cy.get(".govuk-button").contains("Return to LPA list").click();
        cy.contains("LPAs included in the identity check");
        cy.get(".govuk-button").contains("Continue").click();
        //
        cy.contains("What is their date of birth?");

        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("Enter their date of birth");

        cy.reload();
        // cy.screenshot();
        cy.get("#dob-day").type("31", {force: true});
        cy.get("#dob-month").type("10", {force: true});
        cy.get("#dob-year").type(year - 10, {force: true});
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("The person must be 18 years or older.");

        cy.get("#dob-year").clear();
        cy.get("#dob-year").type(year - 20, {force: true});
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the address on the ID document match the address in Sirius?");

        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("Please select an option");

        cy.contains("Yes").click();
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
        cy.get(".govuk-radios__label").contains("Yes").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("Enter the passport number.");

        cy.contains("Help with checking if passport is in date");
        cy.contains("Help with checking if passport is in date").click();
        cy.contains("Enter passport expiry date. For example, 31 03 2012");

        cy.get("#passport").type("123456781", {force: true});
        cy.contains("Yes").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Identity document verified");
    });

    it("lets you identify with Driving licence", () => {

        const d = new Date();
        let year = d.getFullYear();

        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");
        cy.contains("How will you confirm your identity?");
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("How will you confirm your identity?");
        cy.contains("Please select an option");
        cy.contains("UK driving licence (must be current)").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the name match the ID?");
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("LPAs included in the identity check");

        cy.contains("LPAs included in the identity check");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("What is their date of birth?");

        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("Enter their date of birth");

        cy.reload();
        // cy.screenshot();
        cy.get("#dob-day").type("31", {force: true});
        cy.get("#dob-month").type("10", {force: true});
        cy.get("#dob-year").type(year - 10, {force: true});
        cy.get(".govuk-button").contains("Continue").click();
        // cy.screenshot();
        cy.contains("The person must be 18 years or older.");

        cy.get("#dob-year").clear();
        cy.get("#dob-year").type(year - 20, {force: true});
        cy.get(".govuk-button").contains("Continue").click();


        cy.contains("Does the address on the ID document match the address in Sirius?");
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("Please select an option");
        cy.contains("Yes").click();
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
        cy.contains("Yes").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Identity document verified");
    });

    it("lets you identify with National Insurance number and use an alternate address", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
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
        cy.contains("No").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("What is the address?");
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("Enter a postcode");
        cy.get("#postcode").type("SW1A 1AA");
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("What is the address?");
        cy.contains("Select address");
        cy.screenshot();
        cy.contains("The address is not in the list");
        cy.contains("The address is not in the list").click();

        cy.contains("What is the address?");
        cy.get("#line1").type("1 Street");
        cy.get("#town").type("London");
        cy.get("#postcode").type("SW1A 1AA");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the address on the ID document match the address in Sirius?");
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

        cy.contains(".moj-alert", "Identity check passed");
    });

    it("handles 2 LPAs", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-35G3&lpas[]=M-XYXY-YAGB-35G3");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the name match the ID?");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("LPAs included in the identity check");
        cy.contains("Remove").click();

        cy.contains("Remove").should('not.exist');
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("What is their date of birth?");
    });

    it("allows adding new LPA", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the name match the ID?");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("LPAs included in the identity check");
        cy.contains("Remove").should('not.exist');

        cy.contains("Add another LPA").click();
        cy.get("#lpa").type("M-XYXY-YAGB-0000", {force: true});
        cy.contains("Find LPA").click();
        cy.contains("Add LPA to this ID check").click();
        cy.contains("Remove");

        cy.get(".govuk-button").contains("Continue").click();
        cy.contains("What is their date of birth?");
    });

    it("throws duplicate error on attempting to add same LPA", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the name match the ID?");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("LPAs included in the identity check");

        cy.get(".govuk-button").contains("Add another LPA").click();
        cy.contains("Find an LPA to add");
        cy.get("#lpa").should('be.visible').type("M-XYXY-YAGA-0000");        // same LPA number
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.contains("This LPA has already been added to this identity check.");
    });

    it("throws draft error on attempting to add sirius-only LPA", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the name match the ID?");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("LPAs included in the identity check");

        cy.get(".govuk-button").contains("Add another LPA").click();
        cy.contains("Find an LPA to add");
        cy.get("#lpa").should('be.visible').type("M-EMPT-YLPA-CPG3");        // same LPA number
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.contains("This LPA cannot be added as it’s status is set to Draft. LPAs need to be in the In progress status to be added to this ID check.");
    });

    it("throws error on attempting to add registered LPA", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the name match the ID?");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("LPAs included in the identity check");

        cy.get(".govuk-button").contains("Add another LPA").click();
        cy.contains("Find an LPA to add");
        cy.get("#lpa").should('be.visible').type("M-REGI-STER-DLPA");        // registered LPA number
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.contains("This LPA cannot be added as an identity check has already been completed for this LPA");
    });

    it("throws no match error on attempting to add LPA with non-matching ID details", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Does the name match the ID?");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("LPAs included in the identity check");

        cy.get(".govuk-button").contains("Add another LPA").click();
        cy.contains("Find an LPA to add");
        cy.get("#lpa").should('be.visible').type("M-XYXY-YAGA-35G3");        // same LPA number
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.contains("This LPA cannot be added to this ID check because the certificate provider details on this LPA do not match. Edit the certificate provider record in Sirius if appropriate and find again.");
    });
});


