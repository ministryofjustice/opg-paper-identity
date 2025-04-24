describe("Voucher journey", () => {

    beforeEach(function() {
        // create case
        cy.visit("/start?personType=voucher&lpas[]=M-XYXY-YAGA-35G3");
    })

    it("lets you vouch with a National Insurance Number and raise errors if you do not fill in forms", () => {

        // picks up the donors name in the heading
        cy.get("[id=confirm-vouching-heading]").contains("Vouching for Lee Manthrope");

        // forces you to check eligibility and declaration boxes to continue
        cy.get("[id=eligibility-error]").should("not.exist");
        cy.get("[id=declaration-error]").should("not.exist");

        cy.get("[id=continue-with-vouching]").click();

        cy.get("[id=eligibility-error]").should("be.visible");
        cy.get("[id=declaration-error]").should("be.visible");

        cy.get("[id=eligibility_confirmed]").click();
        cy.get("[id=declaration_confirmed]").click();

        cy.get("[id=continue-with-vouching]").click();

        // how will you confirm page
        cy.get(".govuk-heading-xl").contains("How will you confirm your identity?");

        // vouching should not be available as an option.
        cy.contains("Have someone vouch for the identity of the donor").should('not.exist');

        // requires you to select an option
        cy.get("[id=id_method-error]").should("not.exist");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[id=id_method-error]").should("be.visible");

        cy.get("[id=NATIONAL_INSURANCE_NUMBER]").click();
        cy.get(".govuk-button").contains("Continue").click();

        // voucher-name

        // forces you to enter first and last name to continue
        cy.get("[id=first-name-error]").should("not.exist");
        cy.get("[id=first-name-error]").should("not.exist");

        cy.get(".govuk-button").contains("Continue").click();

        cy.get("[id=first-name-error]").should("be.visible");
        cy.get("[id=first-name-error]").should("be.visible");

        cy.get("[id=voucher-first-name]").type("Matthew");
        cy.get("[id=voucher-last-name]").type("Barlow");

        cy.get(".govuk-button").contains("Continue").click();

        // voucher-dob

        // forces you to enter dob to continue
        cy.get("[name=date_problem]").should("not.exist");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[name=date_problem]").should("be.visible");

        cy.get("[id=dob-day]").type("26");
        cy.get("[id=dob-month]").type("05");
        cy.get("[id=dob-year]").type("1991");

        cy.get(".govuk-button").contains("Continue").click();

        // voucher-address

        // postcode
        cy.get("[id=postcode-error]").should("not.exist");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[id=postcode-error]").should("be.visible");

        cy.get("[id=postcode]").type("SW1A1AA");

        cy.get(".govuk-button").contains("Continue").click();

        // select address
        cy.get("[id=address-error]").should("not.exist");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[id=address-error]").should("be.visible");

        cy.get("[id=addresses]").select(1);

        cy.get(".govuk-button").contains("Continue").click();

        // enter manually

        // check populated from one selected previously
        cy.get("[id=line1]").should("not.have.value", "");
        cy.get("[id=town]").should("not.have.value", "");
        cy.get("[id=postcode]").should("not.have.value", "");
        // UK is selected by default
        cy.get("[id=country]").should("have.value", "United Kingdom");

        // address must be populated to continue
        cy.get("[id=line1]").clear();
        cy.get("[id=town]").clear();
        cy.get("[id=postcode]").clear();

        cy.get("[id=line1-error]").should("not.exist");
        cy.get("[id=town-error]").should("not.exist");
        cy.get("[id=postcode-error]").should("not.exist");

        cy.get(".govuk-button").contains("Continue").click();

        cy.get("[id=line1-error]").should("be.visible");
        cy.get("[id=town-error]").should("be.visible");
        cy.get("[id=postcode-error]").should("be.visible");

        cy.get("[id=line1]").type("28 Boat Lane");
        cy.get("[id=town]").type("Rewe");
        cy.get("[id=postcode]").type("EX5 6DB");

        cy.get(".govuk-button").contains("Continue").click();

        cy.get("[id=lpaId]").should('have.length', 1);

        // vouch for another donor
        cy.get(".govuk-button").contains("Vouch for another donor").click();

        cy.get("[id=lpa-number-error]").should("not.exist");
        cy.get(".govuk-button").contains("Find LPA").click();
        cy.get("[id=lpa-number-error]").should("be.visible");

        cy.get("[id=lpa-number]").type("M-VOUC-HFOR-1001");

        cy.get(".govuk-button").contains("Find LPA").click();


        cy.get("[id=declaration-error]").should("not.exist");
        cy.get(".govuk-button").contains("Add this donor to identity check").click();
        cy.get("[id=declaration-error]").should("be.visible");

        cy.get("[id=declaration_confirmed]").click();
        cy.get(".govuk-button").contains("Add this donor to identity check").click();

        // check 2-lpas have been added
        cy.get("[id=lpaId]").should('have.length', 3);

        // check we can remove an LPA
        cy.get(".govuk-link").contains("Remove").click();
        cy.get("[id=lpaId]").should('have.length', 2);

        cy.get(".govuk-button").contains("Continue").click();

        cy.get("[id=nino-error]").should("not.exist");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get("[id=nino-error]").should("be.visible");

        cy.get("[id=nino]").type("NP112233C");
        cy.get(".govuk-button").contains("Continue").click();

        cy.get(".moj-alert--success").contains("Identity document verified");
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

        cy.get(".moj-alert--success").contains("Identity check passed");
    });

    it("lets you vouch via the post-office route", () => {
        cy.jumpToPage("how-will-you-confirm");
        cy.get("input#POST_OFFICE").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.get("[id=PASSPORT]").click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.editVoucherDetails(
            "Matthew Barlow",
            "1991-05-26",
            "28 Boat Lane, Rewe, EX5 6DB"
        );

        cy.get(".govuk-button").contains("Continue").click();

        cy.get('input[name=postoffice]').first().click();
        cy.get(".govuk-button").contains("Continue").click();

        cy.contains("Confirm Post Office route");
        cy.contains("Passport");
        cy.contains("St. Neots");
        cy.contains("Submission deadline");
        cy.get(".govuk-button").contains("Confirm and send letter").click();

        cy.contains("We will send you a letter to take to the Post Office with your chosen identity document.");
    });
});
