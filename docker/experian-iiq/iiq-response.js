const iiqStore = stores.open("iiq");

const body = context.request.body.toString();

const saaEnvelope = `<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <soap:Body>
    <SAAResponse xmlns="http://schema.uk.experian.com/Experian/IdentityIQ/Services/WebService">
      <SAAResult>
        <Control>
          <URN>{{urn}}</URN>
          <AuthRefNo>6B3TGRWSKC</AuthRefNo>
        </Control>
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

const saaEnvelopeNoKbv = `<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <soap:Body>
    <SAAResponse xmlns="http://schema.uk.experian.com/Experian/IdentityIQ/Services/WebService">
      <SAAResult>
        <Control>
          <URN>{{urn}}</URN>
          <AuthRefNo>6B3TGRWSKC</AuthRefNo>
        </Control>
        <Questions>
          {{questions}}
        </Questions>
        <Results>
          <Outcome>Insufficient Questions (Unable to Authenticate)</Outcome>
          <NextTransId>
            <string>END</string>
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
        "VoltWave (✔)",
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
      AnswerList: ["£5.99 (✔)", "£11", "£16.84", "£1.25"],
    },
  },
  {
    QuestionID: "Q00003",
    Text: "What is your mother’s maiden name?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00003",
      FieldType: "G",
      AnswerList: ["Germanotta (✔)", "Gumm", "Micklewhite", "Blythe"],
    },
  },
  {
    QuestionID: "Q00004",
    Text: "What are the last two characters of your car number plate?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00004",
      FieldType: "G",
      AnswerList: ["IF (✔)", "SJ", "WP", "PG"],
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
        "Liberty Trust Bank (✔)",
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
      AnswerList: ["July (✔)", "September", "March", "April"],
    },
  },
  {
    QuestionID: "Q00007",
    Text: "Which company provides your car insurance?",
    Tooltip: "",
    AnswerFormat: {
      Identifier: "A00007",
      FieldType: "G",
      AnswerList: [
        "SafeDrive Insurance (✔)",
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
      AnswerList: ["Pink (✔)", "Green", "Black", "Yellow"],
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

if (body.includes("SAA")) {
  if (body.includes("Thinfile") || true) {
    respond().withContent(
        saaEnvelopeNoKbv
            .replace("{{questions}}", [])
    );
  } else {
    const myQuestions = shuffle(questions)
        .slice(0, 2)
        .map((x) => {
          x.AnswerFormat.AnswerList = shuffle(x.AnswerFormat.AnswerList);
          return { Question: x };
        });

    const id = body.match(
        /<([a-z0-9-]+):ApplicantIdentifier>(.*?)<\/\1:ApplicantIdentifier>/
    )[2];

    let product = ''
    try {
      product = body.match(
          /<([a-z0-9-]+):Product>(.*?)<\/\1:Product>/
      )[2];
    } catch (e) {
    }

    iiqStore.save(
        id,
        JSON.stringify({
          questions: myQuestions.map((x) => ({
            qid: x.Question.QuestionID,
            correct: false,
          })),
          expectedCorrect : product === '4 out of 4' ? 4: 3
        })
    );
    respond().withContent(
        saaEnvelope
            .replace("{{questions}}", myQuestions.map(toxml).join(""))
            .replace("{{urn}}", id)
    );
  }
} else if (body.includes("RTQ")) {
  const answers = body.matchAll(
    /<([a-z0-9-]+):Response><\1:QuestionID>(.*?)<\/\1:QuestionID><\1:AnswerGiven>(.*?)<\/\1:AnswerGiven>/g
  );

  const id = body.match(/<([a-z0-9-]+):URN>(.*?)<\/\1:URN>/)[2];
  const iiqCase = JSON.parse(iiqStore.load(id));

  for (const [, , questionId, answer] of answers) {
    const questionIndex = iiqCase.questions.findIndex(
      (x) => x.qid === questionId
    );
    const question = questions.find((x) => x.QuestionID === questionId);

    iiqCase.questions[questionIndex].correct =
      question?.AnswerFormat.AnswerList[0] === answer;
  }

  const asked = iiqCase.questions.length;
  const correct = iiqCase.questions.filter((q) => q.correct === true).length;

  if (
      correct >= iiqCase.expectedCorrect ||
      asked - correct >= 2 ||
      (iiqCase.expectedCorrect === 4 && (asked - correct) >= 1))
  {
    iiqStore.save(id, JSON.stringify(iiqCase));

    respond()
      .withContent(`<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
      <soap:Body>
        <RTQResponse xmlns="http://schema.uk.experian.com/Experian/IdentityIQ/Services/WebService">
          <RTQResult>
            <Results>
              <Outcome>${
                correct >= iiqCase.expectedCorrect
                  ? "Authentication successful – capture SQ"
                  : "Authentication Unsuccessful"
              }</Outcome>
              <AuthenticationResult>${
                correct >= iiqCase.expectedCorrect ? "Authenticated" : "Not Authenticated"
              }</AuthenticationResult>
              <Questions>
                <Asked>${asked}</Asked>
                <Correct>${correct}</Correct>
                <Incorrect>${asked - correct}</Incorrect>
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
    const newQuestions = shuffle(questions)
      .filter(
        (q) => iiqCase.questions.findIndex((x) => x.qid === q.QuestionID) === -1
      )
      .slice(0, correct >= 2 ? 1 : 2)
      .map((x) => {
        x.AnswerFormat.AnswerList = shuffle(x.AnswerFormat.AnswerList);
        return { Question: x };
      });

    iiqCase.questions.push(
      ...newQuestions.map((x) => ({
        qid: x.Question.QuestionID,
        correct: false,
      }))
    );

    iiqStore.save(id, JSON.stringify(iiqCase));

    respond()
      .withContent(`<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
      <soap:Body>
        <RTQResponse xmlns="http://schema.uk.experian.com/Experian/IdentityIQ/Services/WebService">
          <RTQResult>
            <Questions>
              ${newQuestions.map(toxml).join("")}
            </Questions>
            <Results>
              <NextTransId>
                <string>RTQ</string>
              </NextTransId>
            </Results>
          </RTQResult>
        </RTQResponse>
      </soap:Body>
    </soap:Envelope>`);
  }
} else {
  respond();
}
