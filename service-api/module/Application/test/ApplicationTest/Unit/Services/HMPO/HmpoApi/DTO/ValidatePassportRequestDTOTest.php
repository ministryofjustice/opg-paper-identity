<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\HMPO\HmpoApi\DTO;

use Application\HMPO\HmpoApi\DTO\ValidatePassportRequestDTO;
use Application\Enums\DocumentType;
use Application\HMPO\HmpoApi\HmpoApiException;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\TestCase;

class ValidatePassportRequestDTOTest extends TestCase
{
    private array $baseCase;
    private int $passportNumber;

    public function setUp(): void
    {

        parent::setUp();

        $this->baseCase = [
            'idMethod' => [
                'docType' => DocumentType::Passport->value
            ],
            'claimedIdentity' => [
                'dob' => '2000-01-01',
                'firstName' => 'Mary Ann',
                'lastName' => 'Chapman',
            ]
        ];

        $this->passportNumber = 123456789;
    }

    public function testConstructValidatePassportRequestBody(): void
    {
        $dto = new ValidatePassportRequestDTO(
            CaseData::fromArray($this->baseCase),
            $this->passportNumber
        );

        $expected = [
                "query" =>
                    "query validatePassport(input: \$input) { validationResult passportCancelled passportLostStolen }",
                "variables" => [
                    "input" => [
                        'forenames' => 'Mary Ann',
                        'surname' => 'Chapman',
                        'dateOfBirth' => '2000-01-01',
                        'passportNumber' => 123456789
                    ]
                ]
            ];

        $this->assertEquals($dto->constructValidatePassportRequestBody(), $expected);
    }

    public function testValidatePassportRequestDTOException(): void
    {
        $caseData = $this->baseCase;
        unset($caseData['claimedIdentity']['firstName']);

        $this->expectException(HmpoApiException::class);
        $this->expectExceptionMessage('Case property is not set: first name');

        new ValidatePassportRequestDTO(CaseData::fromArray($caseData), $this->passportNumber);
    }
}
