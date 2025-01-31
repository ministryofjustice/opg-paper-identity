Cypress.Commands.add("getInputByLabel", (label) => {
  return cy
    .contains("label", label)
    .invoke("attr", "for")
    .then((id) => {
      cy.get("#" + id);
    });
});

Cypress.Commands.add("selectKBVAnswer", ({ correct }) => {
  const questions = {
    "Who is your electricity supplier?": {
      correct: "VoltWave",
      incorrect: "Glow Electric",
    },
    "How much was your last phone bill?": {
      correct: "£5.99",
      incorrect: "£11",
    },
    "What is your mother’s maiden name?": {
      correct: "Germanotta",
      incorrect: "Gumm",
    },
    "What are the last two characters of your car number plate?": {
      correct: "IF",
      incorrect: "SJ",
    },
    "Name one of your current account providers": {
      correct: "Liberty Trust Bank",
      incorrect: "Heritage Horizon Bank",
    },
    "In what month did you move into your current house?": {
      correct: "July",
      incorrect: "September",
    },
    "Which company provides your car insurance?": {
      correct: "SafeDrive Insurance",
      incorrect: "Guardian Drive Assurance",
    },
    "What colour is your front door?": {
      correct: "Pink",
      incorrect: "Green",
    },
  };

  cy.get("h1")
    .invoke("text")
    .then((question) => {
      const answer = questions[question][correct ? "correct" : "incorrect"];

      cy.contains(answer).click();
    });
});


Cypress.Commands.add("jumpToPage", (page) => {
  return cy
  .url()
  .then(url => {
    cy.visit(`/${ url.split('/')[3]}/${ page }`)
  });
});

Cypress.Commands.add("editVoucherDetails", (name, dob, address) => {
  if (name) {
    let [firstName, lastName] = name.split(" ", 2);

    cy.jumpToPage('vouching/voucher-name')

    cy.get("[id=voucher-first-name]").type(firstName);
    cy.get("[id=voucher-last-name]").type(lastName);
    cy.get(".govuk-button").contains("Continue").click();
  }
  if (dob) {
    let [year, month, day] = dob.split("-", 3);

    cy.jumpToPage('vouching/voucher-dob')

    cy.get("[id=dob-day]").type(day);
    cy.get("[id=dob-month]").type(month);
    cy.get("[id=dob-year]").type(year);
    cy.get(".govuk-button").contains("Continue").click();
  }
  if (address) {
    let [line1, town, postcode] = address.split(", ", 3);

    cy.jumpToPage('vouching/enter-address-manual')

    cy.get("[id=line1]").type(line1);
    cy.get("[id=town]").type(town);
    cy.get("[id=postcode]").type(postcode);
    cy.get(".govuk-button").contains("Continue").click();
  }
  });
