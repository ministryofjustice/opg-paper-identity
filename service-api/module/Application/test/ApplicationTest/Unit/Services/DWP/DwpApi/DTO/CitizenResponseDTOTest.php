<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DTO\CitizenResponseDTO;
use PHPUnit\Framework\TestCase;

class CitizenResponseDTOTest extends TestCase
{
    private CitizenResponseDTO $citizenResponseDTO;

    private array $testData = [
        "jsonapi" => [
            "version" => "1.0"
        ],
        "data" => [
            "id" => "uuid-string",
            "type" => "MatchResult",
            "attributes" => [
                "matchingScenario" => "Matched on NINO"
            ]
        ]
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->citizenResponseDTO = new CitizenResponseDTO($this->testData);
    }

    public function testId(): void
    {
        $this->assertEquals('uuid-string', $this->citizenResponseDTO->id());
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     */
    public function type(): void
    {
        $this->assertEquals('MatchResult', $this->citizenResponseDTO->type());
    }

    public function testMatchScenario(): void
    {
        $this->assertEquals("Matched on NINO", $this->citizenResponseDTO->matchScenario());
    }

    public function testVersion(): void
    {
        $this->assertEquals("1.0", $this->citizenResponseDTO->version());
    }

    public function testArray(): void
    {
        $this->assertEquals([
            'id' => 'uuid-string',
            'type' => "MatchResult",
            'matchScenario' => "Matched on NINO",
            'version' => "1.0",
        ], $this->citizenResponseDTO->toArray());
    }
}
