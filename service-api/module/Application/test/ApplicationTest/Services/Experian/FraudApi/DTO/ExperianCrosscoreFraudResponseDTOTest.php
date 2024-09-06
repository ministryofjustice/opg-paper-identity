<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Services\Experian\FraudApi\DTO;

use Application\Experian\Crosscore\FraudApi\DTO\ResponseDTO;
use PHPUnit\Framework\TestCase;

class ExperianCrosscoreFraudResponseDTOTest extends TestCase
{
    private ResponseDTO $experianCrosscoreFraudResponseDTO;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
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
                    ],
                    "score" => 34382247.64365953
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

        $this->experianCrosscoreFraudResponseDTO = new ResponseDTO(
            $this->data
        );
    }

    public function testArray(): void
    {
        $this->assertEquals(
            $this->data,
            $this->experianCrosscoreFraudResponseDTO->toArray()
        );
    }

    public function testResponseHeader(): void
    {
        $this->assertEquals(
            $this->data['responseHeader'],
            $this->experianCrosscoreFraudResponseDTO->responseHeader()
        );
    }

    public function testDecision(): void
    {
        $this->assertEquals(
            'CONTINUE',
            $this->experianCrosscoreFraudResponseDTO->decision()
        );
    }

    public function testScore(): void
    {
        $this->assertEquals(
            34382247.64365953,
            $this->experianCrosscoreFraudResponseDTO->score()
        );
    }
}
