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
            "idMethodIncludingNation" => [
                "id_method" => "NATIONAL_INSURANCE_NUMBER",
                "id_route" => "TELEPHONE",
                "id_country" => "GBR"
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
}
