describe("Identify a Certificate Provider with a fraud checck", () => {
    it("passes fraud check", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0000");

        cy.contains("How will they prove their identity?");
        cy.contains("National Insurance number").click();
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
        cy.contains("Yes").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("National insurance number");
        cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Initial identity confirmation complete");
        cy.get(".govuk-button").contains("Continue").click();
    });

    it("reaches no decision result on insufficient ID data", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0001");

        cy.contains("How will they prove their identity?");
        cy.contains("National Insurance number").click();
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
        cy.contains("Yes").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("National insurance number");
        cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Not enough details to continue with this form of identification");

        cy.get(".govuk-button").contains("Try a different method").click();
        cy.contains("Which document will they take to the Post Office?");
    });

    it("reaches a stop result on high fraud risk", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0002");

        cy.contains("How will they prove their identity?");
        cy.contains("National Insurance number").click();
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
        cy.contains("Yes").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("National insurance number");
        cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Stop response received from fraud check service.");
    });

    it("reaches no decision result on insufficient KBV data", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-XYXY-YAGA-0010");

        cy.contains("How will they prove their identity?");
        cy.contains("National Insurance number").click();
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
        cy.contains("Yes").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("National insurance number");
        cy.getInputByLabel("National Insurance number").type("AA 12 34 56 A");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Initial identity confirmation complete");
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Not enough details to continue with this form of identification");
    });
});


