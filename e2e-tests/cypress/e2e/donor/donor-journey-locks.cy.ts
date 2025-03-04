describe("Identify a Donor", () => {
    it("shows completion banner on start page after successful case completion", () => {
        cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.getInputByLabel("National Insurance number").type("NP 112233 C");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.selectKBVAnswer({correct: true});
        cy.get(".govuk-button").contains("Continue").click();
        cy.selectKBVAnswer({correct: true});
        cy.get(".govuk-button").contains("Continue").click();
        cy.selectKBVAnswer({correct: true});
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains(".moj-banner", "Identity check passed");

        cy.jumpToPage("how-will-you-confirm");
        cy.contains("The identity check has already been completed");
        cy.contains("Post Office verification is not presently available");


        cy.get("National insurance number").should("not.exist");
        cy.get("Passport").should("not.exist");
        cy.get("Driving licence").should("not.exist");
    });

    it("shows document pass banner on start page once ID verified" , () => {
        cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.getInputByLabel("National Insurance number").type("NP 112233 C");
        cy.get(".govuk-button").contains("Continue").click();

        cy.jumpToPage("how-will-you-confirm");
        cy.contains("The donor has already proved their identity over the phone with a valid document");
        cy.contains("Post Office");
        cy.contains("Have someone vouch for the identity of the donor");
        cy.contains("The donor cannot do any of the above (Court of Protection)");
        cy.contains('Preferred: ID over the phone').should('not.exist');
    });


    it("shows document fail banner on start page once ID verification fails" , () => {
        cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.getInputByLabel("National Insurance number").type("AA 12 34 56 D");
        cy.get(".govuk-button").contains("Continue").click();

        cy.jumpToPage("how-will-you-confirm");
        cy.contains("The donor cannot prove their identity over the phone because they have tried before and their details did not match the document provided.");
        cy.contains('Preferred: ID over the phone').should('not.exist');

    });

    it("shows document fail banner on start page after KBV fail" , () => {
        cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.getInputByLabel("National Insurance number").type("NP 112233 C");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.selectKBVAnswer({correct: false});
        cy.get(".govuk-button").contains("Continue").click();
        cy.selectKBVAnswer({correct: false});
        cy.get(".govuk-button").contains("Continue").click();

        cy.jumpToPage("how-will-you-confirm");
        cy.contains("The donor cannot ID over the phone or have someone vouch for them due to a lack of available information from Experian or a failure to answer the security questions correctly on a previous occasion.");
        cy.contains('Preferred: ID over the phone').should('not.exist');
    });

});
