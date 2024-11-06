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

        $this->experianCrosscoreFraudResponseDTO = new ResponseDTO(
            $this->data
        );
    }

    public function testArray(): void
    {
        $this->assertEquals(
            [
                "decision" => "CONTINUE",
                "score" => 265,
            ],
            $this->experianCrosscoreFraudResponseDTO->toArray()
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
            265,
            $this->experianCrosscoreFraudResponseDTO->score()
        );
    }
}
