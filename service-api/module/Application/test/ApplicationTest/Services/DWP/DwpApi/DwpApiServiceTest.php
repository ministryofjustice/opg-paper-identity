<?php

declare(strict_types=1);

namespace ApplicationTest\Services\DWP\DwpApi;

use Application\DWP\AuthApi\AuthApiService;
use Application\DWP\DwpApi\DTO\CitizenResponseDTO;
use Application\DWP\DwpApi\DTO\DetailsResponseDTO;
use Application\DWP\DwpApi\DwpApiException;
use Application\Model\Entity\CaseData;
use GuzzleHttp\Exception\ClientException;
use Application\DWP\DwpApi\DwpApiService;
use Application\DWP\DwpApi\DTO\CitizenRequestDTO;
use Application\DWP\DwpApi\DTO\DetailsRequestDTO;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Laminas\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DwpApiServiceTest extends TestCase
{
    private AuthApiService $dwpAuthApiService;
    private DwpApiService $dwpApiService;
    private LoggerInterface&MockObject $logger;
    private const CASE = [
        "id" => "b3ed53a7-9df8-4eb5-9726-abd763e6d595",
        "personType" => "donor",
        "lpas" => [
            "M-XYXY-YAGA-35G3"
        ],
        "documentComplete" => false,
        "identityCheckPassed" => null,
        "searchPostcode" => null,
        "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
        "kbvQuestions" => [
        ],
        "idMethodIncludingNation" => [
            "id_method" => "NATIONAL_INSURANCE_NUMBER",
            "id_route" => "TELEPHONE",
            "id_country" => "GBR",
            "id_value" => "ZZ123456A"
        ],
        "caseProgress" => [
            "abandonedFlow" => null,
            "docCheck" => [
                "idDocument" => "NATIONAL_INSURANCE_NUMBER",
                "state" => true
            ],
            "kbvs" => null,
            "fraudScore" => [
                "decision" => "ACCEPT",
                "score" => 265
            ]
        ],
        "claimedIdentity" => [
            "dob" => "1986-09-03",
            "firstName" => "Lee",
            "lastName" => "Manthrope",
            "address" => [
                "postcode" => "SO15 3AA",
                "country" => "GB",
                "line3" => "",
                "town" => "Southamption",
                "line2" => "",
                "line1" => "18 BOURNE COURT"
            ],
            "professionalAddress" => null
        ]
    ];

    private const DETAILS_RESPONSE = [
        "jsonapi" => [
            "version" => ""
        ],
        "links" => [
            "self" => ""
        ],
        "data" => [
            "id" => "",
            "type" => "Citizen",
            "attributes" => [
                "guid" => "",
                "nino" => "",
                "identityVerificationStatus" => "verified",
                "sex" => "",
                "statusIndicator" => false,
                "name" => [
                    "title" => "Mr",
                    "firstName" => "Lee",
                    "middleNames" => "",
                    "lastName" => "Manthrope",
                    "metadata" => [
                        "verificationType" => "self_asserted",
                        "startDate" => "2024-12-11",
                        "endDate" => "2024-12-11"
                    ]
                ],
                "alternateName" => [
                    "title" => "",
                    "firstName" => "",
                    "middleNames" => "",
                    "lastName" => "",
                    "metadata" => [
                        "verificationType" => "self_asserted",
                        "startDate" => "2024-12-11",
                        "endDate" => "2024-12-11"
                    ]
                ],
                "requestedName" => [
                    "requestedName" => "",
                    "metadata" => [
                        "verificationType" => "self_asserted",
                        "startDate" => "2024-12-11",
                        "endDate" => "2024-12-11"
                    ]
                ],
                "dateOfDeath" => [
                    "date" => "",
                    "metadata" => [
                        "verificationType" => "self_asserted",
                        "startDate" => "2024-12-11",
                        "endDate" => "2024-12-11"
                    ]
                ],
                "dateOfBirth" => [
                    "date" => "1986-09-03",
                    "metadata" => [
                        "verificationType" => "self_asserted",
                        "startDate" => "2024-12-11",
                        "endDate" => "2024-12-11"
                    ]
                ],
                "accessibilityNeeds" => [
                    [
                        "type" => "braille",
                        "metadata" => [
                            "verificationType" => "self_asserted",
                            "startDate" => "2024-12-11",
                            "endDate" => "2024-12-11"
                        ]
                    ]
                ],
                "safeguarding" => [
                    "type" => "potentially_violent",
                    "metadata" => [
                        "verificationType" => "self_asserted",
                        "startDate" => "2024-12-11",
                        "endDate" => "2024-12-11"
                    ]
                ],
                "nationality" => [
                    "nationality" => "british",
                    "metadata" => [
                        "verificationType" => "self_asserted",
                        "startDate" => "2024-12-11",
                        "endDate" => "2024-12-11"
                    ]
                ],
                "contactDetails" => [
                    [
                        "contactType" => "home_telephone_number",
                        "value" => "07745690909",
                        "preferredContactIndicator" => false,
                        "metadata" => [
                            "verificationType" => "self_asserted",
                            "startDate" => "2024-12-11",
                            "endDate" => "2024-12-11"
                        ]
                    ]
                ],
                "warningDetails" => [
                    "warnings" => [
                        [
                            "id" => "",
                            "links" => [
                                "about" => ""
                            ],
                            "status" => "",
                            "code" => "",
                            "title" => "",
                            "detail" => "",
                            "source" => [
                                "pointer" => "",
                                "parameter" => ""
                            ]
                        ]
                    ]
                ]
            ],
            "relationships" => [
                "current-residential-address" => [
                    "links" => [
                        "self" => ""
                    ]
                ],
                "current-correspondence-address" => [
                    "links" => [
                        "self" => ""
                    ]
                ],
                "addresses" => [
                    "links" => [
                        "self" => ""
                    ]
                ],
                "relationships" => [
                    "links" => [
                        "self" => ""
                    ]
                ],
                "claims" => [
                    "links" => [
                        "self" => ""
                    ]
                ]
            ]
        ]
    ];

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $clientCitizen = $this->createMock(Client::class);
        $clientMatch = $this->createMock(Client::class);
        $this->dwpAuthApiService = $this->createMock(AuthApiService::class);

        $this->dwpApiService = new DwpApiService(
            $clientCitizen,
            $clientMatch,
            $this->dwpAuthApiService,
            $this->logger,
            []
        );
    }

    /**
     * @dataProvider ninoData
     */
    public function testNinoFragment(string $nino, string $fragment): void
    {
        $this->assertEquals($fragment, $this->dwpApiService->makeNinoFragment($nino));
    }

    public static function ninoData(): array
    {
        return [
            [
                "AA 12 23 34 C",
                "334C"
            ],
            [
                "AA122334C",
                "334C"
            ],
            [
                " AA 12 23 34 C ",
                "334C"
            ],
            [
                " AA122334C ",
                "334C"
            ],
        ];
    }

    /**
     * @dataProvider requestBodyData
     */
    public function testConstructCitizenRequestBody(
        array             $expected,
        CitizenRequestDTO $dto
    ): void
    {
        $this->assertEquals(
            $expected,
            $this->dwpApiService->constructCitizenRequestBody($dto),
        );
    }

    public static function requestBodyData(): array
    {
        return [
            [
                [
                    "jsonapi" => [
                        "version" => "1.0"
                    ],
                    "data" => [
                        "type" => "Match",
                        "attributes" => [
                            "dateOfBirth" => "1986-09-03",
                            "ninoFragment" => "456A",
                            "firstName" => "Lee",
                            "lastName" => "Manthrope",
                            "postcode" => "SO15 3AA",
                            "contactDetails" => [
                                ""
                            ]
                        ]
                    ]
                ],
                new CitizenRequestDTO(CaseData::fromArray(static::CASE))
            ]
        ];
    }

    /**
     * @return void
     */
    public function testMakeCitizenMatchRequest(): void
    {
        $successMockResponseData = [
            "jsonapi" => [
                "version" => "1.0"
            ],
            "data" => [
                "id" => "be62ed49-5407-4023-844c-97159ec80411",
                "type" => "MatchResult",
                "attributes" => [
                    "matchingScenario" => "Matched on NINO"
                ]
            ]
        ];

        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $successClient,
            $successClient,
            $this->dwpAuthApiService,
            $this->logger,
            []
        );

        $this->assertEquals(
            new CitizenResponseDTO($successMockResponseData),
            $dwpApiService->makeCitizenMatchRequest(
                new CitizenRequestDTO(CaseData::fromArray(static::CASE))
            )
        );
    }

    /**
     * @return void
     */
    public function testMakeCitizenMatchRequestNotEnoughData(): void
    {
        $successMockResponseData = [
            "jsonapi" => [
                "version" => "1.0"
            ],
            "data" => [
                "id" => "",
                "type" => "MatchResult",
                "attributes" => [
                    "matchingScenario" => ""
                ]
            ]
        ];

        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $successClient,
            $successClient,
            $this->dwpAuthApiService,
            $this->logger,
            []
        );

        $this->assertEquals(
            new CitizenResponseDTO($successMockResponseData),
            $dwpApiService->makeCitizenMatchRequest(
                new CitizenRequestDTO(CaseData::fromArray(static::CASE))
            )
        );
    }

    public function testMakeCitizenMatchRequestWith400Response(): void
    {
        $this->expectException(ClientException::class);
        $failMock = new MockHandler([
            new GuzzleResponse(400, [], json_encode([])),
        ]);
        $failHandlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $failHandlerStack]);

        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([])),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $failClient,
            $successClient,
            $this->dwpAuthApiService,
            $this->logger,
            []
        );

        $dwpApiService->makeCitizenMatchRequest(new CitizenRequestDTO(CaseData::fromArray(static::CASE)));
    }

    public function testMakeCitizenDetailsRequest(): void
    {
        $successMockResponseData = static::DETAILS_RESPONSE;

        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $successClient,
            $successClient,
            $this->dwpAuthApiService,
            $this->logger,
            []
        );

        $this->assertEquals(
            new DetailsResponseDTO($successMockResponseData),
            $dwpApiService->makeCitizenDetailsRequest(
                new DetailsRequestDTO('case-id-string')
            )
        );
    }

    public function testMakeCitizenDetailsRequestWith400Response(): void
    {
        $this->expectException(ClientException::class);
        $failMock = new MockHandler([
            new GuzzleResponse(400, [], json_encode([])),
        ]);
        $failHandlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $failHandlerStack]);

        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode([])),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $failClient,
            $failClient,
            $this->dwpAuthApiService,
            $this->logger,
            []
        );

        $dwpApiService->makeCitizenDetailsRequest(new DetailsRequestDTO('case-id-string'));
    }


    /**
     * @dataProvider compareRecordsData
     */
    public function testCompareRecords(
        array $expected,
        CaseData $caseData,
        CitizenResponseDTO $citizenResponseDTO,
        DetailsResponseDTO $detailsResponseDTO,
    ): void {
        $this->assertEquals(
            $expected,
            $this->dwpApiService->compareRecords(
                $caseData,
                $detailsResponseDTO,
                $citizenResponseDTO
            )
        );
    }

    public static function compareRecordsData(): array
    {
        $successMatchDTO = new CitizenResponseDTO([
            "jsonapi" => [
                "version" => "1.0"
            ],
            "data" => [
                "id" => "be62ed49-5407-4023-844c-97159ec80411",
                "type" => "MatchResult",
                "attributes" => [
                    "matchingScenario" => "Matched on NINO"
                ]
            ]
        ]);

        $noMatchDTO = new CitizenResponseDTO([
            "jsonapi" => [
                "version" => "1.0"
            ],
            "data" => [
                "id" => "",
                "type" => "MatchResult",
                "attributes" => [
                    "matchingScenario" => ""
                ]
            ]
        ]);

        $successDetailsDTO = new DetailsResponseDTO(static::DETAILS_RESPONSE);

        $failDetailsResponse = static::DETAILS_RESPONSE;
        $failDetailsResponse['data']['attributes']['identityVerificationStatus'] = '';

        $failDetailsDTO = new DetailsResponseDTO($failDetailsResponse);

        $caseData = CaseData::fromArray(static::CASE);

        return [
            [
                [
                    $caseData->idMethodIncludingNation->id_value,
                    'PASS',
                    Response::STATUS_CODE_200
                ],
                $caseData,
                $successMatchDTO,
                $successDetailsDTO
            ],
            [
                [
                    $caseData->idMethodIncludingNation->id_value,
                    'NO_MATCH',
                    Response::STATUS_CODE_200
                ],
                $caseData,
                $noMatchDTO,
                $failDetailsDTO
            ],
            [
                [
                    $caseData->idMethodIncludingNation->id_value,
                    'NO_MATCH',
                    Response::STATUS_CODE_200
                ],
                $caseData,
                $successMatchDTO,
                $failDetailsDTO
            ],
            [
                [
                    $caseData->idMethodIncludingNation->id_value,
                    'NO_MATCH',
                    Response::STATUS_CODE_200
                ],
                $caseData,
                $noMatchDTO,
                $successDetailsDTO
            ],
        ];
    }
}
