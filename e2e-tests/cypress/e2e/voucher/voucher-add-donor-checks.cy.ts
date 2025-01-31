describe("Voucher journey will check details against actors on the LPA", () => {

    beforeEach(function() {
        // create case
        cy.visit("/start?personType=voucher&lpas[]=M-XYXY-YAGA-35G3");
    })

    it("will stop you adding LPAs which are already on the ID check", () => {
        cy.editVoucherDetails(
            "Matthew Barlow",
            "1991-05-26",
            "28 Boat Lane, Rewe, EX5 6DB"
        );

        cy.get(".govuk-button").contains("Vouch for another donor").click();
        cy.get("[id=lpa-number]").type("M-VOUC-HFOR-2001");
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.get("[id=declaration_confirmed]").click();
        cy.get(".govuk-button").contains("Add this donor to identity check").click();

        cy.get(".govuk-button").contains("Vouch for another donor").click();
        cy.get("[id=lpa-number]").type("M-VOUC-HFOR-2001");
        cy.get(".govuk-button").contains("Find LPA").click();

        cy.get("[id=problemMessage]").contains("This LPA has already been added to this identity check.")

    });

    it("will stop you adding LPAs where you match an actor on the LPA", () => {
        cy.editVoucherDetails(
            "Yasmin Hardy",
            "1990-11-07",
            "5 Shore Street, STOKE PRIOR, B60 3XH"
        );

        cy.get(".govuk-button").contains("Vouch for another donor").click();
        cy.get("[id=lpa-number]").type("M-VOUC-HFOR-2001");
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.get("[id=warningMessage]").contains("The person vouching cannot have the same name and date of birth as an attorney.")
    });

});