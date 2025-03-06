describe("Identify a Certificate Provider", () => {
    it("prevents start on cancelled lpa", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-CANC-ELLE-DPG3", {failOnStatusCode: false});
        cy.contains("ID check has status: cancelled and cannot be started");
    });

    it("prevents start on cannot register lpa", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-NOTR-EGIS-RPG3", {failOnStatusCode: false});
        cy.contains("ID check has status: cannot-register and cannot be started");
    });

    it("prevents start on de-registered lpa", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-DERE-GIST-RPG3", {failOnStatusCode: false});
        cy.contains("ID check has status: de-registered and cannot be started");
    });

    it("allows start on do not register lpa", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-DNOT-REGI-RPG3");
        cy.contains("How will you confirm your identity?");
    });

    it("prevents start on expired lpa", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-EXPI-REDQ-RPG3", {failOnStatusCode: false});
        cy.contains("ID check has status: expired and cannot be started");
    });

    it("allows start on statutory waiting period lpa", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-STAT-WAIT-RPG3");
        cy.contains("How will you confirm your identity?");
    });

    it("prevents start on suspended lpa", () => {
        cy.visit("/start?personType=certificateProvider&lpas[]=M-SUSP-ENDE-DPG3", {failOnStatusCode: false});
        cy.contains("ID check has status: suspended and cannot be started");
    });
});


