<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Services\Experian\FraudApi;

use Application\Cache\ApcHelper;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthRequestDTO;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiService;
use Application\Services\Experian\FraudApi\DTO\CrosscoreAddressDTO;
use Application\Services\Experian\FraudApi\DTO\ExperianCrosscoreFraudRequestDTO;
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

    private array $config;

    private ExperianCrosscoreAuthApiService $experianCrosscoreAuthApiService;

    public function setUp(): void
    {
        $this->config = [
            'domain' => 'test.com',
            'tenantId' => 'test'
        ];

        $this->client = $this->createMock(Client::class);
        $apcHelper = $this->createMock(ApcHelper::class);
        $experianCrosscoreAuthRequestDto = new ExperianCrosscoreAuthRequestDTO(
            'username',
            'password',
            'clientId',
            'clientSecret',
        );

        $this->experianCrosscoreAuthApiService = $this->createMock(ExperianCrosscoreAuthApiService::class);

        $this->experianCrosscoreFraudApiService = new ExperianCrosscoreFraudApiService(
            $this->client,
            $this->experianCrosscoreAuthApiService,
            $this->config
        );
    }

//    public function testMakePersonId(): void
//    {
//        $data = [
//            'firstName' => 'firstName',
//            'lastName' => 'lastName',
//            'dob' => '1982-01-01',
//            'address' => [
//                'address_line_1' => 'address_line_1',
//                'address_line_2' => 'address_line_2',
//                'town' => 'town',
//                'postcode' => 'postcode',
//            ]
//        ];
//
//        $dto = new ExperianCrosscoreFraudRequestDTO(
//            $data['firstName'],
//            $data['lastName'],
//            $data['dob'],
//            $data['address']
//        );
//
//        $this->assertEquals(
//            'FL1',
//            $this->experianCrosscoreFraudApiService->makePersonId($dto)
//        );
//    }
//
//
//    public function testGetHeaders(): void
//    {
//        $headers = $this->experianCrosscoreAuthApiService->makeHeaders();
//
//        $this->assertArrayHasKey('Content-Type', $headers);
//        $this->assertArrayHasKey('X-Correlation-Id', $headers);
//        $this->assertArrayHasKey('X-User-Domain', $headers);
//
//        $this->assertMatchesRegularExpression(
//            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
//            $headers['X-Correlation-Id']
//        );
//    }

    public function testGetCredentials(): void
    {
        $credentials = $this->experianCrosscoreAuthApiService->getCredentials();

        $this->assertInstanceOf(ExperianCrosscoreAuthRequestDTO::class, $credentials);
    }

    /**
     * @dataProvider fraudScoreResponseData
     * @param class-string<Throwable>|null $expectedException
     */
    public function testGetFraudScore(
        Client $client,
        ExperianCrosscoreFraudRequestDTO $mockRequestDto,
        ?array $requestData,
        ?array $responseData,
        ?string $expectedException
    ): void {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $this->experianCrosscoreAuthApiService
            ->expects($this->once())
            ->method('retrieveCachedTokenResponse');

//        $this->experianCrosscoreAuthApiService->retrieveCachedTokenResponse()->

        $experianCrosscoreFraudApiService = new ExperianCrosscoreFraudApiService(
            $client,
            $this->experianCrosscoreAuthApiService,
            $this->config
        );

        $response = $experianCrosscoreFraudApiService->getFraudScore($mockRequestDto);

        $this->assertEquals($responseData, $response);
    }

    public static function fraudScoreResponseData(): array
    {
        $mockRequestDto = new ExperianCrosscoreFraudRequestDTO(
            "MARK",
            "ADOLFSON",
            "1955-06-23",
            new CrosscoreAddressDTO(
                "17  FOX LEA WALK",
                "",
                "",
                "CRAMLINGTON",
                "NE23 7TD",
                "UK"
            )
        );

        $mockRequestData = [
            "header" => [
                "tenantId" => "test",
                "requestType" => "FraudScore",
                "clientReferenceId" => "7a9c3f81-33c3-48d1-9064-abb98ecc844a-FraudScore-continue",
                "expRequestId" => "7a9c3f81-33c3-48d1-9064-abb98ecc844a",
                "messageTime" => "2024-09-04T11:26:30Z",
                "options" => [
                ]
            ],
            "payload" => [
                "contacts" => [
                    [
                        "id" => "MA1",
                        "person" => [
                            "personDetails" => [
                                "dateOfBirth" => "1955-06-23"
                            ],
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
                                "buildingName" => "17 FOX LEA WALK",
                                "street" => "",
                                "street2" => "",
                                "postTown" => "CRAMLINGTON",
                                "postal" => "NE23 7TD",
                                "county" => "NORTHUMBERLAND",
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
                "clientReferenceId" => "974daa9e-8128-49cb-9728-682c72fa3801-FraudScore-continue",
                "expRequestId" => "RB000001416866",
                "messageTime" => "2024-09-03T11:19:07Z",
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
                        "decisionTime" => "2024-09-03T11:19:08Z"
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
                        "decisionTime" => "2024-09-03T11:19:08Z"
                    ]
                ],
                "decisionElements" => [
                    [
                        "serviceName" => "uk-crpverify",
                        "applicantId" => "MA_APPLICANT1",
                        "appReference" => "8H9NGXVZZV",
                        "warningsErrors" => [
                        ],
                        "otherData" => [
                            "response" => [
                                "contactId" => "MA1",
                                "nameId" => "MANAME1",
                                "uuid" => "75467c7e-c7ea-4f3a-b02e-3fd0793191b5"
                            ]
                        ],
                        "auditLogs" => [
                            [
                                "eventType" => "BUREAU DATA",
                                "eventDate" => "2024-09-03T11:19:08Z",
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
                                -1
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
                                "dateOfBirth" => "1986-09-03"
                            ],
                            "personIdentifier" => "",
                            "names" => [
                                [
                                    "type" => "CURRENT",
                                    "firstName" => "lee",
                                    "surName" => "manthrope",
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
                                "buildingNumber" => "18",
                                "postal" => "SO15 3AA",
                                "street" => "BOURNE COURT",
                                "postTown" => "southampton",
                                "county" => ""
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
                $mockRequestDto,
                $mockRequestData,
                $successMockResponseData,
                null
            ],
            [
                $fail401Client,
                $mockRequestDto,
                $mockRequestData,
                null,
                ExperianCrosscoreFraudApiException::class,
            ],
            [
                $fail400Client,
                $mockRequestDto,
                $mockRequestData,
                null,
                ExperianCrosscoreFraudApiException::class,
            ]
        ];
    }
}
