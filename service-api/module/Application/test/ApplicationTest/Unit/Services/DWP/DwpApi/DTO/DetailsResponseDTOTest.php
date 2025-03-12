<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DTO\DetailsResponseDTO;
use PHPUnit\Framework\TestCase;

class DetailsResponseDTOTest extends TestCase
{
    private DetailsResponseDTO $detailsResponseDTO;

    private array $testData = [
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

    public function setUp(): void
    {
        parent::setUp();

        $this->detailsResponseDTO = new DetailsResponseDTO($this->testData);
    }

    public function testGuid(): void
    {
        $this->assertEquals(
            "ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2",
            $this->detailsResponseDTO->guid()
        );
    }

    public function testNino(): void
    {
        $this->assertEquals("NP112233C", $this->detailsResponseDTO->nino());
    }

    public function testArray(): void
    {
        $this->assertEquals([
            'guid' => "ab2a482ffd49110eeae81a04005a589a7755c8000f953bef101e95367aab9cf2",
            'nino' => "NP112233C",
        ], $this->detailsResponseDTO->toArray());
    }
}
