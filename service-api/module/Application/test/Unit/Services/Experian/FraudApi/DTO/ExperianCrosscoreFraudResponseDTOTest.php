<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\Experian\FraudApi\DTO;

use Application\Experian\Crosscore\FraudApi\DTO\ResponseDTO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExperianCrosscoreFraudResponseDTOTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    #[DataProvider('data')]
    public function testDto(array $data, array $expected): void
    {
        $experianCrosscoreFraudResponseDTO = new ResponseDTO($data);

        $this->assertEquals(
            $expected,
            $experianCrosscoreFraudResponseDTO->toArray()
        );

        $this->assertEquals(
            $expected['decision'],
            $experianCrosscoreFraudResponseDTO->decision()
        );

        $this->assertEquals(
            $expected['score'],
            $experianCrosscoreFraudResponseDTO->score()
        );
    }

    public static function data(): array
    {
        $continueData = [
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
                        "sequenceId" => "2",
                        "decisionSource" => "MachineLearning",
                        "decision" => "CONTINUE",
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
            ]
        ];

        $noDecisionData = [
            "responseHeader" => [
                "requestType" => "FraudScore",
                "clientReferenceId" => "974daa9e-8128-49cb-9728-682c72fa3801-FraudScore-continue",
                "expRequestId" => "RB000001416866",
                "messageTime" => "2024-09-03T11:19:07Z",
                "overallResponse" => [
                    "decision" => "NODECISION",
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
                        "sequenceId" => "2",
                        "decisionSource" => "MachineLearning",
                        "decision" => "CONTINUE",
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
            ]
        ];

        $stopData = [
            "responseHeader" => [
                "requestType" => "FraudScore",
                "clientReferenceId" => "974daa9e-8128-49cb-9728-682c72fa3801-FraudScore-continue",
                "expRequestId" => "RB000001416866",
                "messageTime" => "2024-09-03T11:19:07Z",
                "overallResponse" => [
                    "decision" => "STOP",
                    "decisionText" => "Stop and Investigate",
                    "decisionReasons" => [
                        "High Risk Machine Learning score",
                        "Processing completed successfully"
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
                        "sequenceId" => "2",
                        "decisionSource" => "MachineLearning",
                        "decision" => "REFER-HIGH",
                        "decisionReasons" => [
                            "High Risk Machine Learning score"
                        ],
                        "score" => 780,
                        "decisionText" => "Stop and Investigate",
                        "nextAction" => "Continue",
                        "appReference" => "",
                        "decisionTime" => "2024-07-25T10:51:47Z"
                    ]
                ],
            ]
        ];

        return [
            [
                $continueData,
                [
                    'decision' => 'CONTINUE',
                    'score' => 265
                ],
            ],
            [
                $noDecisionData,
                [
                    'decision' => 'NODECISION',
                    'score' => 265
                ],
            ],
            [
                $stopData,
                [
                    'decision' => 'STOP',
                    'score' => 780
                ],
            ],
        ];
    }
}
