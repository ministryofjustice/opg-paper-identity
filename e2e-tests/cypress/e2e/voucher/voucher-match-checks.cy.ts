describe("Voucher journey will check details against actors on the LPA", () => {

    beforeEach(function() {
        // creates new case
        cy.visit("/start?personType=voucher&lpas[]=M-XYXY-YAGA-35G3");
    })

    it("will stop you if you share name and dob with the donor", () => {
        cy.jumpToPage("vouching/voucher-name");

        // voucher-name
        cy.get("[id=voucher-first-name]").type("Lee");
        cy.get("[id=voucher-last-name]").type("Manthrope");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=donor_warning]");
        cy.get(".govuk-button").contains("Continue").click();

        // voucher-dob
        cy.get("[id=voucher-dob-day]").type("03");
        cy.get("[id=voucher-dob-month]").type("09");
        cy.get("[id=voucher-dob-year]").type("1986");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=donor_warning]");

        // wont let you past
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=donor_warning]");
    });

    it("will stop you if you share name and dob with an attorney", () => {
        cy.jumpToPage("vouching/voucher-name");

        // voucher-name
        cy.get("[id=voucher-first-name]").type("Josephine");
        cy.get("[id=voucher-last-name]").type("Blick");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=attorney_warning]");
        cy.get(".govuk-button").contains("Continue").click();

        // voucher-dob
        cy.get("[id=voucher-dob-day]").type("18");
        cy.get("[id=voucher-dob-month]").type("09");
        cy.get("[id=voucher-dob-year]").type("1960");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=attorney_warning]");

        // wont let you past
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=attorney_warning]");
    });

    it("will warn you if you share name with an certificate provider", () => {
        cy.jumpToPage("vouching/voucher-name");

        // voucher-name
        cy.get("[id=voucher-first-name]").type("Quincy");
        cy.get("[id=voucher-last-name]").type("Ankunding");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=cp_warning]");
        cy.get(".govuk-button").contains("Continue").click();

        // on dob page
        cy.get(".govuk-heading-xl").contains("What is their date of birth?")
    });

    it("will stop you if you share address donor", () => {
        // enter address manually
        cy.jumpToPage("vouching/enter-address-manual");

        cy.get("[id=line1]").type("18 Bourne Court");
        cy.get("[id=town]").type("Southampton");
        cy.get("[id=postcode]").type("SO15 3AA");

        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=address_warning]");

        // wont let you past
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=address_warning]");
    });
});