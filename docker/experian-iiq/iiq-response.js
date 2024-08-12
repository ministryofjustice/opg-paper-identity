const req = context.request;

const saaEnvelope = `<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <soap:Body>
    <SAAResponse xmlns="http://schema.uk.experian.com/Experian/IdentityIQ/Services/WebService">
      <SAAResult>
        <Questions>
          {{questions}}
        </Questions>
        <Results>
          <Outcome>Authentication Questions returned</Outcome>
          <NextTransId>
            <string>RTQ</string>
          </NextTransId>
        </Results>
      </SAAResult>
    </SAAResponse>
  </soap:Body>
</soap:Envelope>`;

const questions = [
  {
    QuestionID: "Q00001",
    Text: "Who is your electricity supplier?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00001",
      FieldType: "G",
      AnswerList: [
        "VoltWave",
        "Glow Electric",
        "Powergrid Utilities",
        "Bright Bristol Power",
      ],
    },
  },
  {
    QuestionID: "Q00002",
    Text: "How much was your last phone bill?",
    Tooltip: "The approximate amount in £s of your primary mobile phon bill.",
    AnswerFormat: {
      Identifier: "A00002",
      FieldType: "G",
      AnswerList: ["£5.99", "£11", "£16.84", "£1.25"],
    },
  },
  {
    QuestionID: "Q00003",
    Text: "What is your mother’s maiden name?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00003",
      FieldType: "G",
      AnswerList: ["Germanotta", "Gumm", "Micklewhite", "Blythe"],
    },
  },
  {
    QuestionID: "Q00004",
    Text: "What are the last two characters of your car number plate?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00004",
      FieldType: "G",
      AnswerList: ["IF", "SJ", "WP", "PG"],
    },
  },
  {
    QuestionID: "Q00005",
    Text: "Name one of your current account providers",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00005",
      FieldType: "G",
      AnswerList: [
        "Liberty Trust Bank",
        "Heritage Horizon Bank",
        "Prosperity Peak Financial",
        "Summit State Saving",
      ],
    },
  },
  {
    QuestionID: "Q00006",
    Text: "In what month did you move into your current house?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00006",
      FieldType: "G",
      AnswerList: ["July", "September", "March", "April"],
    },
  },
  {
    QuestionID: "Q00007",
    Text: "Who is your electricity supplier?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00007",
      FieldType: "G",
      AnswerList: [
        "SafeDrive Insurance",
        "Guardian Drive Assurance",
        "ShieldSafe",
        "Swift Cover Protection",
      ],
    },
  },
  {
    QuestionID: "Q00008",
    Text: "What colour is your front door?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00008",
      FieldType: "G",
      AnswerList: ["Pink", "Green", "Black", "Yellow"],
    },
  },
];

function toxml(o) {
  if (typeof o === "string") {
    return o;
  }

  if (typeof o === "object") {
    let build = "";

    Object.entries(o).forEach(([k, v]) => {
      if (Array.isArray(v)) {
        v.forEach((e) => {
          build += `<${k}>${toxml(e)}</${k}>`;
        });
      } else {
        build += `<${k}>${toxml(v)}</${k}>`;
      }
    });

    return build;
  }

  throw new Error(`cannot handle type ${typeof o}`);
}

function shuffle(a) {
  return a
    .map((value) => ({ value, sort: Math.random() }))
    .sort((a, b) => a.sort - b.sort)
    .map(({ value }) => value);
}

if (req.body.includes("SAA")) {
  const myQuestions = shuffle(questions)
    .slice(0, 4)
    .map((x) => {
      x.AnswerFormat.AnswerList = shuffle(x.AnswerFormat.AnswerList);
      return { Question: x };
    });

  respond().withContent(
    saaEnvelope.replace("{{questions}}", myQuestions.map(toxml).join(""))
  );
} else if (req.body.includes("RTQ")) {
  const answers = req.body.matchAll(
    /<([a-z0-9-]+):Response><\1:QuestionID>(.*?)<\/\1:QuestionID><\1:AnswerGiven>(.*?)<\/\1:AnswerGiven>/g
  );

  let correct = 0;
  for (const [, , questionId, answer] of answers) {
    const question = questions.find((x) => x.QuestionID === questionId);
    if (question?.AnswerFormat.AnswerList[0] === answer) correct++;
  }

  respond()
    .withContent(`<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <soap:Body>
    <RTQResponse xmlns="http://schema.uk.experian.com/Experian/IdentityIQ/Services/WebService">
      <RTQResult>
        <Results>
          <Outcome>${correct === 4 ? 'Authentication successful – capture SQ' : 'Authentication Unsuccessful'}</Outcome>
          <AuthenticationResult>${correct === 4 ? 'Authenticated' : 'Not Authenticated'}</AuthenticationResult>
          <Questions>
            <Asked>4</Asked>
            <Correct>${correct}</Correct>
            <Incorrect>${4 - correct}</Incorrect>
          </Questions>
          <NextTransId>
            <string>END</string>
          </NextTransId>
        </Results>
      </RTQResult>
    </RTQResponse>
  </soap:Body>
</soap:Envelope>`);
} else {
  respond();
}
