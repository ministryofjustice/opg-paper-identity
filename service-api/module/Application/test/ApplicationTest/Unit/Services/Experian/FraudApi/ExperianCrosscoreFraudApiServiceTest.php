<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Services\Experian\FraudApi;

use Application\Experian\Crosscore\AuthApi\AuthApiService;
use Application\Experian\Crosscore\FraudApi\DTO\RequestDTO;
use Application\Experian\Crosscore\FraudApi\DTO\ResponseDTO;
use Application\Experian\Crosscore\FraudApi\FraudApiException;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

class ExperianCrosscoreFraudApiServiceTest extends TestCase
{
    private array $config;

    private AuthApiService&MockObject $experianCrosscoreAuthApiService;


    private LoggerInterface&MockObject $logger;

    public function setUp(): void
    {
        $this->config = [
            'domain' => 'test.com',
            'tenantId' => 'test'
        ];

        $this->experianCrosscoreAuthApiService = $this->createMock(AuthApiService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @dataProvider fraudScoreResponseData
     * @param class-string<Throwable>|null $expectedException
     */
    public function testGetFraudScore(
        Client $client,
        RequestDTO $mockRequestDto,
        ?ResponseDTO $responseData,
        ?string $expectedException
    ): void {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        } else {
            $this->experianCrosscoreAuthApiService
                ->expects($this->once())
                ->method('retrieveCachedTokenResponse');
        }

        $experianCrosscoreFraudApiService = new FraudApiService(
            $client,
            $this->experianCrosscoreAuthApiService,
            $this->logger,
            $this->config
        );

        $response = $experianCrosscoreFraudApiService->getFraudScore($mockRequestDto);

        $this->assertEquals($responseData, $response);
    }

    public static function fraudScoreResponseData(): array
    {
        $mockRequestDto = new RequestDTO(
            "MARK",
            "ADOLFSON",
            "1955-06-23",
            new \Application\Experian\Crosscore\FraudApi\DTO\AddressDTO(
                "17  FOX LEA WALK",
                "",
                "",
                "CRAMLINGTON",
                "NE23 7TD",
                "UK"
            )
        );

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

        $successMockResponseDTO = new ResponseDTO($successMockResponseData);

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
            new GuzzleResponse(200, [], json_encode($successMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $fail401Mock = new MockHandler([
            new GuzzleResponse(401, [], json_encode($failUnauthorisedResponse, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($fail401Mock);
        $fail401Client = new Client(['handler' => $handlerStack]);

        $fail400Mock = new MockHandler([
            new GuzzleResponse(401, [], json_encode($failBadRequestResponse, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($fail400Mock);
        $fail400Client = new Client(['handler' => $handlerStack]);


        return [
            [
                $successClient,
                $mockRequestDto,
                $successMockResponseDTO,
                null
            ],
            [
                $fail401Client,
                $mockRequestDto,
                null,
                FraudApiException::class,
            ],
            [
                $fail400Client,
                $mockRequestDto,
                null,
                FraudApiException::class,
            ]
        ];
    }
}
