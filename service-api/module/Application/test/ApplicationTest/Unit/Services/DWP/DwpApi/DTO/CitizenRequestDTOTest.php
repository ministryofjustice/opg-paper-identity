<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\DWP\DwpApi\DTO;

use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\DWP\DwpApi\DTO\CitizenRequestDTO;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\TestCase;

class CitizenRequestDTOTest extends TestCase
{
    private CitizenRequestDTO $citizenRequestDTO;

    private const NINO = "NP112233C";

    private const CASE = [
        "id" => "b3ed53a7-9df8-4eb5-9726-abd763e6d595",
        "personType" => "donor",
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

    public function setUp(): void
    {
        parent::setUp();

        $nino = "NP112233C";

        $case = CaseData::fromArray([
            "id" => "b3ed53a7-9df8-4eb5-9726-abd763e6d595",
            "personType" => "donor",
            "lpas" => [
                "M-XYXY-YAGA-35G3"
            ],
            "documentComplete" => false,
            "identityCheckPassed" => null,
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "idMethod" => [
                "docType" => DocumentType::NationalInsuranceNumber->value,
                "idRoute" => IdRoute::KBV->value,
                "idCountry" => "GBR"
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
        ]);

        $this->citizenRequestDTO = new CitizenRequestDTO($case, $nino);
    }

    public function testFirstName(): void
    {
        $this->assertEquals('Lee', $this->citizenRequestDTO->firstName());
    }

    public function testLastName(): void
    {
        $this->assertEquals('Manthrope', $this->citizenRequestDTO->lastName());
    }

    public function testDob(): void
    {
        $this->assertEquals("1986-09-03", $this->citizenRequestDTO->dob());
    }

    public function testPostcode(): void
    {
        $this->assertEquals("SO15 3AA", $this->citizenRequestDTO->postcode());
    }

    public function testAddressLine1(): void
    {
        $this->assertEquals('18 BOURNE COURT', $this->citizenRequestDTO->addressLine1());
    }

    public function testNino(): void
    {
        $this->assertEquals('NP112233C', $this->citizenRequestDTO->nino());
    }

    public function testArray(): void
    {
        $this->assertEquals([
            'firstName' => 'Lee',
            'lastName' => 'Manthrope',
            'dob' => "1986-09-03",
            'postcode' => "SO15 3AA",
            'addressLine1' => '18 BOURNE COURT',
            'nino' => 'NP112233C'
        ], $this->citizenRequestDTO->toArray());
    }



    /**
     * @dataProvider postcodeData
     */
    public function testPostcodeFormatter(string $postcode, string $expected): void
    {
        $this->assertEquals($expected, $this->citizenRequestDTO->makeFormattedPostcode($postcode));
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
        $this->assertEquals($fragment, $this->citizenRequestDTO->makeNinoFragment($nino));
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
        array $expected
    ): void {
        $this->assertEquals(
            $expected,
            $this->citizenRequestDTO->constructCitizenRequestBody(),
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
}
