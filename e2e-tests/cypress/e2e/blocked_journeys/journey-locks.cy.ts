describe("Identify a Donor", () => {
    it("shows completion banner on start page after successful case completion and blocks all routes", () => {
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
        cy.contains(".moj-alert", "Identity check passed");

        cy.jumpToPage("how-will-you-confirm");
        cy.contains("The identity check has already been completed");
        cy.contains("Post Office verification is not presently available");

        cy.get("National insurance number").should("not.exist");
        cy.get("Passport").should("not.exist");
        cy.get("Driving licence").should("not.exist");
        cy.get("Post Office").should("not.exist");
        cy.get("vouch for the identity of the donor").should("not.exist");
        cy.get("Court of Protection").should("not.exist");
    });

    it("shows banner and blocks kbv routes on start page once ID doc check already done and kbvs abandoned " , () => {
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
        cy.get(".govuk-link").contains("I need to prove my identity another way").click();

        cy.contains("The donor cannot ID over the phone due to a lack of available security questions or failure to answer them correctly.");
        cy.contains("Alternatively, you can take one of the identity documents listed below to a Post Office").should("not.exist");
        cy.contains("You can take one of the identity documents listed below to a Post Office")
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
        cy.contains("The donor cannot prove their identity over the phone because their ID document could not be verified.");
        cy.contains("Alternatively, you can take one of the identity documents listed below to a Post Office").should("not.exist");
        cy.contains("You can take one of the identity documents listed below to a Post Office")
        cy.contains("Have someone vouch for the identity of the donor");
        cy.contains("The donor cannot do any of the above (Court of Protection)");
        cy.contains('Preferred: ID over the phone').should('not.exist');

    });

    it("shows document warning banner on start page when no KBV questions are available (thin file)" , () => {
        cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G5");  // mock case which give thin-file

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.getInputByLabel("National Insurance number").type("NP112233C");
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.jumpToPage("how-will-you-confirm");
        cy.contains("The donor cannot ID over the phone due to a lack of available security questions or failure to answer them correctly.");
        cy.contains('Preferred: ID over the phone').should('not.exist');
        cy.contains("Alternatively, you can take one of the identity documents listed below to a Post Office").should("not.exist");
        cy.contains("You can take one of the identity documents listed below to a Post Office")
        cy.contains("Have someone vouch for the identity of the donor");
        cy.contains("The donor cannot do any of the above (Court of Protection)");

    });

    it("shows banner on start page after KBV fail" , () => {
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
        cy.contains("The donor cannot ID over the phone due to a lack of available security questions or failure to answer them correctly.");
        cy.contains('Preferred: ID over the phone').should('not.exist');
        cy.contains("Alternatively, you can take one of the identity documents listed below to a Post Office").should("not.exist");
        cy.contains("You can take one of the identity documents listed below to a Post Office")
        cy.contains("Have someone vouch for the identity of the donor");
        cy.contains("The donor cannot do any of the above (Court of Protection)");

    });

    it("shows banner on start page after KBV fail, vouching is not available as fraud-check returns STOP" , () => {
        cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G0");  //mock case which returns STOP result.

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
        cy.contains("The donor cannot ID over the phone or have someone vouch for them due to a failure to answer the security questions correctly.");
        cy.contains('Preferred: ID over the phone').should('not.exist');
        cy.contains("Alternatively, you can take one of the identity documents listed below to a Post Office").should("not.exist");
        cy.contains("You can take one of the identity documents listed below to a Post Office");
        cy.contains("Have someone vouch for the identity of the donor").should('not.exist');
        cy.contains("The donor cannot do any of the above (Court of Protection)");


    });

    it("blocks National Insurance Number as an option on start page if this gives ambiguous match when attempted" , () => {
        cy.visit("/start?personType=donor&lpas[]=M-XYXY-YAGA-35G3");

        cy.contains("How will you confirm your identity?");
        cy.get("label").contains("National insurance number").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.get(".govuk-button").contains("Continue").click();
        cy.getInputByLabel("National Insurance number").type("NP 123123 C");  // mock nino which give ambiguous match
        cy.get(".govuk-button").contains("Continue").click();
        cy.contains('Unable to verify National Insurance number.');
        cy.jumpToPage("how-will-you-confirm");
        cy.contains("National Insurance number could not be verified over the phone, choose an alternate ID method below.");
    });

    it("shows no LPA found on Sirius returns not found" , () => {
        cy.visit("/start?personType=donor&lpas[]=M-1234-5678-90AB");

        cy.contains("LPA not found for M-1234-5678-90AB");
    });

    it("shows how confirm page if at least one LPA is found" , () => {
        cy.visit("/start?personType=donor&lpas[]=M-1234-5678-90AB&lpas[]=M-XYXY-YAGA-35G3");

        cy.contains("How will you confirm your identity?");
    });

});
