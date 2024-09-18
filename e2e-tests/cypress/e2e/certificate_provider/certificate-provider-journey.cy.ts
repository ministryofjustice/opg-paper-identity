describe("Identify a Certificate Provider", () => {
    it("lets you identify with National Insurance number", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-1234-5678-90AB");

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

        cy.contains("Select answer");

        cy.selectKBVAnswer({ correct: true });
        cy.get(".govuk-button").contains("Continue").click();

        cy.selectKBVAnswer({ correct: true });
        cy.get(".govuk-button").contains("Continue").click();

        cy.selectKBVAnswer({ correct: true });
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains(".moj-banner", "Identity check passed");
    });
});
