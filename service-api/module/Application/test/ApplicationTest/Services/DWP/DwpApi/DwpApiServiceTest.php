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
    private AuthApiService&MockObject $dwpAuthApiService;
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
        "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
        "idMethodIncludingNation" => [
            "id_method" => "NATIONAL_INSURANCE_NUMBER",
            "id_route" => "TELEPHONE",
            "id_country" => "GBR",
            "id_value" => "NP112233C"
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
                "nino" => "NP112233C",
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
                "dateOfBirth" => [
                    "date" => "1986-09-03",
                    "metadata" => [
                        "verificationType" => "self_asserted",
                        "startDate" => "2024-12-11",
                        "endDate" => "2024-12-11"
                    ]
                ],
            ]
        ]
    ];

    private const NINO = "NP112233C";

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $client = $this->createMock(Client::class);
        $this->dwpAuthApiService = $this->createMock(AuthApiService::class);

        $this->dwpApiService = new DwpApiService(
            $client,
            $this->dwpAuthApiService,
            $this->logger,
            '',
            ''
        );
    }

    /**
     * @dataProvider postcodeData
     */
    public function testPostcodeFormatter(string $postcode, string $expected): void
    {
        $this->assertEquals($expected, $this->dwpApiService->makeFormattedPostcode($postcode));
    }

    public static function postcodeData(): array
    {
        return [
            [
                "SW1A1AA",
                "SW1A 1AA"
            ],
            [
                "SW1A-1AA",
                "SW1A 1AA"
            ],
            [
                " SW1A 1AA",
                "SW1A 1AA"
            ],
            [
                "SW1A 1AA",
                "SW1A 1AA"
            ],
        ];
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
                "2334"
            ],
            [
                "AA122334C",
                "2334"
            ],
            [
                " AA 12 23 34 C ",
                "2334"
            ],
            [
                " AA122334C ",
                "2334"
            ],
        ];
    }

    /**
     * @dataProvider requestBodyData
     */
    public function testConstructCitizenRequestBody(
        array $expected,
        CitizenRequestDTO $dto
    ): void {
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
                            "ninoFragment" => "2233",
                            "firstName" => "Lee",
                            "lastName" => "Manthrope",
                            "postcode" => "SO15 3AA",
                            "contactDetails" => [
                                ""
                            ]
                        ]
                    ]
                ],
                new CitizenRequestDTO(
                    CaseData::fromArray(static::CASE),
                    static::NINO
                ),
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
            new GuzzleResponse(200, [], json_encode($successMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $successClient,
            $this->dwpAuthApiService,
            $this->logger,
            '',
            ''
        );

        $this->assertEquals(
            new CitizenResponseDTO($successMockResponseData),
            $dwpApiService->makeCitizenMatchRequest(
                new CitizenRequestDTO(
                    CaseData::fromArray(static::CASE),
                    self::NINO
                )
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
            new GuzzleResponse(200, [], json_encode($successMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $successClient,
            $this->dwpAuthApiService,
            $this->logger,
            '',
            ''
        );

        $this->assertEquals(
            new CitizenResponseDTO($successMockResponseData),
            $dwpApiService->makeCitizenMatchRequest(
                new CitizenRequestDTO(
                    CaseData::fromArray(static::CASE),
                    self::NINO
                )
            )
        );
    }

    public function testMakeCitizenMatchRequestWith400Response(): void
    {
        $this->expectException(ClientException::class);
        $failMock = new MockHandler([
            new GuzzleResponse(400, [], json_encode([], JSON_THROW_ON_ERROR)),
        ]);
        $failHandlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $failHandlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $failClient,
            $this->dwpAuthApiService,
            $this->logger,
            '',
            ''
        );

        $dwpApiService->makeCitizenMatchRequest(new CitizenRequestDTO(
            CaseData::fromArray(static::CASE),
            self::NINO
        ));
    }

    public function testMakeCitizenDetailsRequest(): void
    {
        $successMockResponseData = static::DETAILS_RESPONSE;

        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode($successMockResponseData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $successClient,
            $this->dwpAuthApiService,
            $this->logger,
            '',
            ''
        );

        $this->assertEquals(
            new DetailsResponseDTO($successMockResponseData),
            $dwpApiService->makeCitizenDetailsRequest(
                new DetailsRequestDTO('case-id-string'),
                self::NINO
            )
        );
    }

    public function testMakeCitizenDetailsRequestWith400Response(): void
    {
        $this->expectException(ClientException::class);
        $failMock = new MockHandler([
            new GuzzleResponse(400, [], json_encode([], JSON_THROW_ON_ERROR)),
        ]);
        $failHandlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $failHandlerStack]);

        $this->dwpAuthApiService->expects(self::once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $failClient,
            $this->dwpAuthApiService,
            $this->logger,
            '',
            ''
        );

        $dwpApiService->makeCitizenDetailsRequest(
            new DetailsRequestDTO('case-id-string'),
            self::NINO
        );
    }


    /**
     * @dataProvider compareRecordsData
     */
    public function testCompareRecords(
        bool $expected,
        CaseData $caseData,
        CitizenResponseDTO $citizenResponseDTO,
        DetailsResponseDTO $detailsResponseDTO,
    ): void {
        $this->assertEquals(
            $expected,
            $this->dwpApiService->compareRecords(
                $caseData,
                $detailsResponseDTO,
                $citizenResponseDTO,
                self::NINO
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
                true,
                $caseData,
                $successMatchDTO,
                $successDetailsDTO
            ],
            [
                false,
                $caseData,
                $noMatchDTO,
                $failDetailsDTO
            ],
            [
                false,
                $caseData,
                $successMatchDTO,
                $failDetailsDTO
            ],
            [
                false,
                $caseData,
                $noMatchDTO,
                $successDetailsDTO
            ],
        ];
    }
}
