<?php

declare(strict_types=1);

namespace ApplicationTest\Services\DWP\DwpApi;

use Application\Cache\ApcHelper;
use Application\DWP\AuthApi\AuthApiService;
use Application\DWP\AuthApi\DTO\RequestDTO;
use Application\DWP\DwpApi\DwpApiService;
use Application\DWP\DwpApi\DTO\CitizenRequestDTO;
use Application\DWP\DwpApi\DTO\DetailsRequestDTO;
use Application\Model\Entity\CaseData;
use GuzzleHttp\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

class DwpApiServiceTest extends TestCase
{
    //dce -it api ./vendor/bin/phpunit ./module/Application/test/ApplicationTest/Services/DWP/DwpApi


    private Client $client;
    private Client $clientCitizen;

    private Client $clientMatch;

    private ApcHelper $apcHelper;

    private RequestDTO $dwpAuthRequestDto;

    private CitizenRequestDTO $citizenRequestDTO;

    private AuthApiService $dwpAuthApiService;

    private DwpApiService $dwpApiService;

    private LoggerInterface&MockObject $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->clientCitizen = $this->createMock(Client::class);
        $this->clientMatch = $this->createMock(Client::class);
        $this->client = $this->createMock(Client::class);
        $this->apcHelper = $this->createMock(ApcHelper::class);
        $this->dwpAuthRequestDto = new RequestDTO(
            'username',
            'password',
            'bundle',
            'privateKey',
        );

        $this->dwpAuthApiService = new AuthApiService(
            $this->client,
            $this->apcHelper,
            $this->logger,
            $this->dwpAuthRequestDto
        );

        $this->dwpApiService = new DwpApiService(
            $this->clientCitizen,
            $this->clientMatch,
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
    public function testConstructCitizenRequestBody(array $expected, CitizenRequestDTO $dto): void
    {
        $this->assertEquals(
            $expected,
            $this->dwpApiService->constructCitizenRequestBody($dto),
        );
    }

    public static function requestBodyData(): array
    {
        $case = [
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
                "id_value" => "NP123456A"
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
                new CitizenRequestDTO($case)
            ]
        ];
    }
}
