<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Services\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DTO\DetailsResponseDTO;
use PHPUnit\Framework\TestCase;

class DetailsResponseDTOTest extends TestCase
{
    private DetailsResponseDTO $detailsResponseDTO;

    private array $testData = [
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
                "nino" => "AA112233A",
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
        parent::setUp();

        $this->detailsResponseDTO = new DetailsResponseDTO($this->testData);
    }

    public function testFirstName(): void
    {
        $this->assertEquals('Lee', $this->detailsResponseDTO->firstName());
    }

    public function testLastName(): void
    {
        $this->assertEquals('Manthrope', $this->detailsResponseDTO->lastName());
    }

    public function testDob(): void
    {
        $this->assertEquals("1986-09-03", $this->detailsResponseDTO->dob());
    }

    public function testNino(): void
    {
        $this->assertEquals("AA112233A", $this->detailsResponseDTO->nino());
    }

    public function testVerified(): void
    {
        $this->assertEquals("verified", $this->detailsResponseDTO->verified());
    }

    public function testRaw(): void
    {
        $this->assertEquals($this->testData, $this->detailsResponseDTO->raw());
    }

    public function testArray(): void
    {
        $this->assertEquals([
            'firstName' => 'Lee',
            'lastName' => "Manthrope",
            'dob' => "1986-09-03",
            'nino' => "AA112233A",
            'verified' => "verified"
        ], $this->detailsResponseDTO->toArray());
    }
}
