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
