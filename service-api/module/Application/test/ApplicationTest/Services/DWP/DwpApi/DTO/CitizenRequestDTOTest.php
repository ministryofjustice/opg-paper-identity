<?php

declare(strict_types=1);

namespace ApplicationTest\Services\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DTO\CitizenRequestDTO;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\TestCase;

class CitizenRequestDTOTest extends TestCase
{
    private CitizenRequestDTO $citizenRequestDTO;

    public function setUp(): void
    {
        parent::setUp();

        $this->citizenRequestDTO = new CitizenRequestDTO([
            "firstName" => "Mary Ann",
            "lastName" => "Chapman",
            "dob" => "1949-01-01",
            "personType" => "donor",
            "address" => [
                "line1" => "1 Street",
                "line2" => "Road",
                "town" => "town",
                "postcode" => "SW1B 1BB",
                "country" => "UK"
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9"
            ]
        ]);
    }

    public function testFirstName(): void
    {
        $this->assertEquals('Mary Ann', $this->citizenRequestDTO->firstName());
    }

    public function testLastName(): void
    {
        $this->assertEquals('Chapman', $this->citizenRequestDTO->lastName());
    }

    public function testDob(): void
    {
        $this->assertEquals("1949-01-01", $this->citizenRequestDTO->dob());
    }

    public function testPostcode(): void
    {
        $this->assertEquals("SW1B 1BB", $this->citizenRequestDTO->postcode());
    }

    public function testAddressLine1(): void
    {
        $this->assertEquals('1 Street', $this->citizenRequestDTO->addressLine1());
    }

    public function testArray(): void
    {
        $this->assertEquals([
            'firstName' => 'Mary Ann',
            'lastName' => 'Chapman',
            'dob' => "1949-01-01",
            'postcode' => "SW1B 1BB",
            'addressLine1' => '1 Street',
        ], $this->citizenRequestDTO->toArray());
    }
}
