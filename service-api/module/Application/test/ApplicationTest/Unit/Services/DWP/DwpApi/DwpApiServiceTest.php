<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Services\DWP\DwpApi;

use Application\DWP\AuthApi\AuthApiService;
use Application\DWP\DwpApi\DTO\CitizenRequestDTO;
use Application\DWP\DwpApi\DTO\CitizenResponseDTO;
use Application\DWP\DwpApi\DTO\DetailsRequestDTO;
use Application\DWP\DwpApi\DTO\DetailsResponseDTO;
use Application\DWP\DwpApi\DwpApiService;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Enums\PersonType;
use Application\Model\Entity\CaseData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DwpApiServiceTest extends TestCase
{
    private AuthApiService&MockObject $dwpAuthApiService;
    private DwpApiService $dwpApiService;
    private LoggerInterface&MockObject $logger;

    private array $headerOptions = [];
    private const CASE = [
        "id" => "b3ed53a7-9df8-4eb5-9726-abd763e6d595",
        "personType" => PersonType::Donor->value,
        "lpas" => [
            "M-XYXY-YAGA-35G3"
        ],
        "documentComplete" => false,
        "identityCheckPassed" => null,
        "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
        "idMethod" => [
            "docType" => DocumentType::NationalInsuranceNumber->value,
            "idRoute" => IdRoute::KBV->value,
            "idCountry" => "GBR",
            "id_value" => "NP112233C"
        ],
        "caseProgress" => [
            "abandonedFlow" => null,
            "docCheck" => [
                "idDocument" => DocumentType::NationalInsuranceNumber->value,
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
            "version" => "1.0"
        ],
        "data" => [
            "relationships" => [
                "relationships" => [
                    "links" => [
                        "self" => "https://capi.sandbox.citizen-information-nonprod.dwpcloud.uk/capi/v2/" .
                            "citizens/ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2/relationships"

                    ]
                ],
                "addresses" => [
                    "links" => [
                        "self" => "https://capi.sandbox.citizen-information-nonprod.dwpcloud.uk/capi/v2/" .
                            "citizens/ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2/relationships"

                    ]
                ],
                "claims" => [
                    "links" => [
                        "self" => "https://capi.sandbox.citizen-information-nonprod.dwpcloud.uk/capi/v2/" .
                            "citizens/ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2/relationships"

                    ]
                ],
                "current-correspondence-address" => [
                    "links" => [
                        "self" => "https://capi.sandbox.citizen-information-nonprod.dwpcloud.uk/capi/v2/" .
                            "citizens/ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2/relationships"

                    ]
                ],
                "current-residential-address" => [
                    "links" => [
                        "self" => "https://capi.sandbox.citizen-information-nonprod.dwpcloud.uk/capi/v2/" .
                            "citizens/ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2/relationships"

                    ]
                ]
            ],
            "attributes" => [
                "guid" => "ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2",
                "nino" => "NP112233C"
            ],
            "id" => "ab2a482ffd49110eeae81a04005a589a0c80c619847602d1923875462900dfaf",
            "type" => "Citizen"
        ],
        "links" => [
            "self" => "https://capi.sandbox.citizen-information-nonprod.dwpcloud.uk/capi/v2/" .
                "citizens/ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2/relationships"

        ]
    ];

    private const NINO = "NP112233C";

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $client = $this->createMock(Client::class);
        $this->dwpAuthApiService = $this->createMock(AuthApiService::class);

        $this->headerOptions['policy_id'] = "policy-id";
        $this->headerOptions['context'] = 'context';

        $this->dwpApiService = new DwpApiService(
            $client,
            $this->dwpAuthApiService,
            $this->logger,
            $this->headerOptions
        );
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
            $this->headerOptions
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
            $this->headerOptions
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
            $this->headerOptions
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
            $this->headerOptions
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
            $this->headerOptions
        );

        $dwpApiService->makeCitizenDetailsRequest(
            new DetailsRequestDTO('case-id-string'),
            self::NINO
        );
    }


    #[DataProvider('compareRecordsData')]
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
                "type" => "",
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
                $noMatchDTO,
                $successDetailsDTO
            ],
        ];
    }

    public function testValidateNino(): void
    {
        $successMockResponseMatchData = [
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
        $successMockResponseDetailsData = static::DETAILS_RESPONSE;

        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode($successMockResponseMatchData, JSON_THROW_ON_ERROR)),
            new GuzzleResponse(200, [], json_encode($successMockResponseDetailsData, JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $this->dwpAuthApiService->expects($this->exactly(2))
            ->method('retrieveCachedTokenResponse')
            ->willReturn('access_token');

        $dwpApiService = new DwpApiService(
            $successClient,
            $this->dwpAuthApiService,
            $this->logger,
            $this->headerOptions
        );

        $this->assertEquals(
            true,
            $dwpApiService->validateNino(
                CaseData::fromArray(static::CASE),
                'NP112233C',
                'correlation-id'
            )
        );
    }
}
