<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Services\Experian\FraudApi;

use Application\Cache\ApcHelper;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthRequestDTO;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiService;
use Application\Services\Experian\FraudApi\ExperianCrosscoreFraudApiException;
use Application\Services\Experian\FraudApi\ExperianCrosscoreFraudApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Throwable;

class ExperianCrosscoreFraudApiServiceTest extends TestCase
{
    private Client $client;

    private ExperianCrosscoreFraudApiService $experianCrosscoreFraudApiService;

    private ExperianCrosscoreAuthApiService $experianCrosscoreAuthApiService;

    public function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $apcHelper = $this->createMock(ApcHelper::class);
        $experianCrosscoreAuthRequestDto = new ExperianCrosscoreAuthRequestDTO(
            'username',
            'password',
            'clientId',
            'clientSecret',
        );

        $this->experianCrosscoreAuthApiService = new ExperianCrosscoreAuthApiService(
            $this->client,
            $apcHelper,
            $experianCrosscoreAuthRequestDto
        );

        $this->experianCrosscoreFraudApiService = new ExperianCrosscoreFraudApiService(
            $this->client,
            $this->experianCrosscoreAuthApiService
        );
    }

    public function testGetHeaders(): void
    {
        $headers = $this->experianCrosscoreAuthApiService->makeHeaders();

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('X-Correlation-Id', $headers);
        $this->assertArrayHasKey('X-User-Domain', $headers);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $headers['X-Correlation-Id']
        );
    }

    public function testGetCredentials(): void
    {
        $credentials = $this->experianCrosscoreAuthApiService->getCredentials();

        $this->assertInstanceOf(ExperianCrosscoreAuthRequestDTO::class, $credentials);
    }

    /**
     * @dataProvider fraudScoreResponseData
     * @param class-string<Throwable>|null $expectedException
     */
    public function testGetFraudScore(Client $client, ?array $requestData, ?array $responseData, ?string $expectedException): void
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $experianCrosscoreFraudApiService = new ExperianCrosscoreFraudApiService(
            $client,
            $this->experianCrosscoreAuthApiService
        );

        $response = $experianCrosscoreFraudApiService->getFraudScore();

        $this->assertEquals($responseData, $response->toArray());
    }

    public static function fraudScoreResponseData(): array
    {
        $mockRequestData = [
            "header" => [
                "tenantId" => "tenantID",
                "requestType" => "requestType",
                "clientReferenceId" => "expUuid-FraudScore-continue",
                "expRequestId" => null,
                "messageTime" => "timestamp",
                "options" => [
                ]
            ],
            "payload" => [
                "contacts" => [
                    [
                        "id" => "MA1",
                        "person" => [
                            "personDetails" => [
                                "maritalStatus" => "SIN",
                                "occupancyStatus" => "OWE",
                                "dateOfBirth" => "1955-06-23"
                            ],
                            "personIdentifier" => "",
                            "names" => [
                                [
                                    "type" => "CURRENT",
                                    "title" => "MR",
                                    "firstName" => "MARK",
                                    "surName" => "ADOLFSON",
                                    "middleNames" => "",
                                    "id" => "MANAME1"
                                ]
                            ]
                        ],
                        "addresses" => [
                            [
                                "id" => "MACADDRESS1",
                                "addressType" => "CURRENT",
                                "indicator" => "RESIDENTIAL",
                                "buildingNumber" => "17",
                                "postal" => "NE23 7TD",
                                "street" => "FOX LEA WALK",
                                "postTown" => "CRAMLINGTON",
                                "county" => "NORTHUMBERLAND",
                                "timeAtAddress" => [
                                    "value" => "36",
                                    "unit" => "MONTH"
                                ],
                                "residentFrom" => [
                                    "fullDateFrom" => "2010-08-25"
                                ],
                                "residentTo" => [
                                    "fullDateTo" => "2020-08-25"
                                ]
                            ],
                            [
                                "id" => "MAPADDRESS1",
                                "indicator" => "RESIDENTIAL",
                                "addressType" => "PREVIOUS",
                                "buildingNumber" => "11",
                                "postal" => "SA18 3NJ",
                                "street" => "ARGYLL TERRAC",
                                "postTown" => "Stockport",
                                "county" => "Greater Manchester",
                                "timeAtAddress" => [
                                    "value" => "36",
                                    "unit" => "MONTH"
                                ],
                                "residentFrom" => [
                                    "fullDateFrom" => "2000-08-25"
                                ],
                                "residentTo" => [
                                    "fullDateTo" => "2010-08-25"
                                ]
                            ]
                        ],
                        "telephones" => [
                            [
                                "id" => "MATELEPHONE1",
                                "number" => "0115854258",
                                "phoneIdentifier" => "HOME"
                            ]
                        ],
                        "emails" => [
                            [
                                "id" => "MAEMAIL1",
                                "type" => "HOME",
                                "email" => "TestFraud@hotmail.com"
                            ]
                        ],
                        "bankAccount" => [
                            "id" => "MABANK1",
                            "sortCode" => "070116",
                            "clearAccountNumber" => "00136076",
                            "timeWithBank" => [
                                "value" => "3",
                                "unit" => "YEAR"
                            ]
                        ]
                    ]
                ],
                "control" => [
                    [
                        "option" => "ML_MODEL_CODE",
                        "value" => "bfs"
                    ]
                ],
                "application" => [
                    "originalRequestTime" => "2018-03-18T02:20:04Z",
                    "status" => "ACCPT",
                    "type" => "CREDIT",
                    "productDetails" => [
                        "productCode" => "DEV_PC",
                        "productAmount" => [
                            "amount" => "50000"
                        ],
                        "depositAmount" => [
                            "amount" => "5000"
                        ],
                        "lendingTerm" => [
                            "duration" => "36",
                            "unit" => "MONTH"
                        ]
                    ],
                    "applicants" => [
                        [
                            "id" => "MA_APPLICANT1",
                            "contactId" => "MA1",
                            "type" => "INDIVIDUAL",
                            "applicantType" => "MAIN_APPLICANT",
                            "consent" => "true"
                        ]
                    ]
                ],
                "source" => "WEB"
            ]
        ];

        $successMockResponseData = [
            "responseHeader" => [
                "requestType" => "FraudScore",
                "clientReferenceId" => "-FraudScore-continue",
                "expRequestId" => "RB000000000126",
                "messageTime" => "2024-07-25T10:51:46Z",
                "overallResponse" => [
                    "decision" => "CONTINUE",
                    "decisionText" => "Continue",
                    "decisionReasons" => [
                        "Processing completed successfully",
                        "Low Risk Machine Learning score"
                    ],
                    "recommendedNextActions" => [
                    ],
                    "spareObjects" => [
                    ]
                ],
                "responseCode" => "R0201",
                "responseType" => "INFO",
                "responseMessage" => "Workflow Complete.",
                "tenantID" => "623c97f7ff2e44528aa3fba116372d",
                "category" => "COMPLIANCE_INQUIRY"
            ],
            "clientResponsePayload" => [
                "orchestrationDecisions" => [
                    [
                        "sequenceId" => "1",
                        "decisionSource" => "uk-crp",
                        "decision" => "CONTINUE",
                        "decisionReasons" => [
                            "Processing completed successfully"
                        ],
                        "score" => 0,
                        "decisionText" => "Continue",
                        "nextAction" => "Continue",
                        "decisionTime" => "2024-07-25T10:51:47Z"
                    ],
                    [
                        "sequenceId" => "2",
                        "decisionSource" => "MachineLearning",
                        "decision" => "ACCEPT",
                        "decisionReasons" => [
                            "Low Risk Machine Learning score"
                        ],
                        "score" => 265,
                        "decisionText" => "Continue",
                        "nextAction" => "Continue",
                        "appReference" => "",
                        "decisionTime" => "2024-07-25T10:51:47Z"
                    ]
                ],
                "decisionElements" => [
                    [
                        "serviceName" => "uk-crpverify",
                        "applicantId" => "MA_APPLICANT1",
                        "appReference" => "8GYMT9LX8W",
                        "warningsErrors" => [
                        ],
                        "otherData" => [
                            "response" => [
                                "contactId" => "MA1",
                                "nameId" => "MANAME1",
                                "uuid" => "dd6d2775-fb55-4631-bbb4-b5dc241fb4fb"
                            ]
                        ],
                        "auditLogs" => [
                            [
                                "eventType" => "BUREAU DATA",
                                "eventDate" => "2024-07-25T10:51:47Z",
                                "eventOutcome" => "No Match Found"
                            ]
                        ]
                    ],
                    [
                        "serviceName" => "MachineLearning",
                        "normalizedScore" => 100,
                        "score" => 265,
                        "appReference" => "fraud-score-1.0",
                        "otherData" => [
                            "probabilities" => [
                                0.73476599388745,
                                0.26523400611255
                            ],
                            "probabilityMultiplier" => 1000,
                            "modelInputs" => [
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                -1,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                -1,
                                -1,
                                0,
                                0,
                                0
                            ]
                        ],
                        "decisions" => [
                            [
                                "element" => "Reason 1",
                                "value" => "6.7",
                                "reason" => "PA04 - Number of previous vehicle financing applications"
                            ]
                        ]
                    ]
                ]
            ],
            "originalRequestData" => [
                "contacts" => [
                    [
                        "id" => "MA1",
                        "person" => [
                            "personDetails" => [
                                "maritalStatus" => "SIN",
                                "occupancyStatus" => "OWE",
                                "dateOfBirth" => "1955-06-23"
                            ],
                            "personIdentifier" => "",
                            "names" => [
                                [
                                    "type" => "CURRENT",
                                    "title" => "MR",
                                    "firstName" => "MARK",
                                    "surName" => "ADOLFSON",
                                    "middleNames" => "",
                                    "id" => "MANAME1"
                                ]
                            ]
                        ],
                        "addresses" => [
                            [
                                "id" => "MACADDRESS1",
                                "addressType" => "CURRENT",
                                "indicator" => "RESIDENTIAL",
                                "buildingNumber" => "17",
                                "postal" => "NE23 7TD",
                                "street" => "FOX LEA WALK",
                                "postTown" => "CRAMLINGTON",
                                "county" => "NORTHUMBERLAND",
                                "timeAtAddress" => [
                                    "value" => "36",
                                    "unit" => "MONTH"
                                ],
                                "residentFrom" => [
                                    "fullDateFrom" => "2010-08-25"
                                ],
                                "residentTo" => [
                                    "fullDateTo" => "2020-08-25"
                                ]
                            ],
                            [
                                "id" => "MAPADDRESS1",
                                "indicator" => "RESIDENTIAL",
                                "addressType" => "PREVIOUS",
                                "buildingNumber" => "11",
                                "postal" => "SA18 3NJ",
                                "street" => "ARGYLL TERRAC",
                                "postTown" => "Stockport",
                                "county" => "Greater Manchester",
                                "timeAtAddress" => [
                                    "value" => "36",
                                    "unit" => "MONTH"
                                ],
                                "residentFrom" => [
                                    "fullDateFrom" => "2000-08-25"
                                ],
                                "residentTo" => [
                                    "fullDateTo" => "2010-08-25"
                                ]
                            ]
                        ],
                        "telephones" => [
                            [
                                "id" => "MATELEPHONE1",
                                "number" => "0115854258",
                                "phoneIdentifier" => "HOME"
                            ]
                        ],
                        "emails" => [
                            [
                                "id" => "MAEMAIL1",
                                "type" => "HOME",
                                "email" => "TestFraud@hotmail.com"
                            ]
                        ],
                        "bankAccount" => [
                            "id" => "MABANK1",
                            "sortCode" => "070116",
                            "clearAccountNumber" => "00136076",
                            "timeWithBank" => [
                                "value" => "3",
                                "unit" => "YEAR"
                            ]
                        ]
                    ]
                ],
                "control" => [
                    [
                        "option" => "ML_MODEL_CODE",
                        "value" => "bfs"
                    ]
                ],
                "application" => [
                    "originalRequestTime" => "2018-03-18T02:20:04Z",
                    "status" => "ACCPT",
                    "type" => "CREDIT",
                    "productDetails" => [
                        "productCode" => "DEV_PC",
                        "productAmount" => [
                            "amount" => "50000"
                        ],
                        "depositAmount" => [
                            "amount" => "5000"
                        ],
                        "lendingTerm" => [
                            "duration" => "36",
                            "unit" => "MONTH"
                        ]
                    ],
                    "applicants" => [
                        [
                            "id" => "MA_APPLICANT1",
                            "contactId" => "MA1",
                            "type" => "INDIVIDUAL",
                            "applicantType" => "MAIN_APPLICANT",
                            "consent" => "true"
                        ]
                    ]
                ],
                "source" => ""
            ]
        ];

        $failUnauthorisedResponse = [
            "errors" => [
                [
                    "errorType" => "Unauthorized",
                    "message" => "Access token is invalid"
                ]
            ],
            "success" => false
        ];


        $failBadRequestResponse = [
            "responseHeader" => [
                "requestType" => "",
                "clientReferenceId" => "",
                "expRequestId" => "",
                "responseCode" => "R0102",
                "responseType" => "ERROR",
                "responseMessage" => "JSON request is not well-formed.",
                "tenantID" => ""
            ]
        ];


        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $fail401Mock = new MockHandler([
            new GuzzleResponse(401, [], json_encode($failUnauthorisedResponse)),
        ]);
        $handlerStack = HandlerStack::create($fail401Mock);
        $fail401Client = new Client(['handler' => $handlerStack]);

        $fail400Mock = new MockHandler([
            new GuzzleResponse(401, [], json_encode($failBadRequestResponse)),
        ]);
        $handlerStack = HandlerStack::create($fail400Mock);
        $fail400Client = new Client(['handler' => $handlerStack]);



        return [
            [
                $successClient,
                $mockRequestData,
                $successMockResponseData,
                null
            ],
            [
                $fail401Client,
                $mockRequestData,
                null,
                ExperianCrosscoreFraudApiException::class,
            ],
            [
                $fail400Client,
                $mockRequestData,
                null,
                ExperianCrosscoreFraudApiException::class,
            ]
        ];
    }
}
