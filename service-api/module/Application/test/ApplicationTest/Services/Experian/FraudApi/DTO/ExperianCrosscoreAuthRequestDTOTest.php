<?php

declare(strict_types=1);

namespace ApplicationTest\Services\Experian\FraudApi\DTO;

use Application\Services\Experian\FraudApi\DTO\ExperianCrosscoreFraudRequestDTO;
use PHPUnit\Framework\TestCase;

class ExperianCrosscoreFraudRequestDTOTest extends TestCase
{
    private readonly ExperianCrosscoreFraudRequestDTO $experianCrosscoreFraudRequestDTO;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'dob' => '1982-01-01',
            'address' => [
                'address_line_1' => 'address_line_1',
                'address_line_2' => 'address_line_2',
                'town' => 'town',
                'postcode' => 'postcode',
            ]
        ];

        $this->experianCrosscoreFraudRequestDTO = new ExperianCrosscoreFraudRequestDTO(
            $this->data['firstName'],
            $this->data['lastName'],
            $this->data['dob'],
            $this->data['address']
        );
    }

    public function testFirstName(): void
    {
        $this->assertEquals('firstName', $this->experianCrosscoreFraudRequestDTO->firstName());
    }

    public function testLastName(): void
    {
        $this->assertEquals('lastName', $this->experianCrosscoreFraudRequestDTO->lastName());
    }

    public function testDob(): void
    {
        $this->assertEquals('1982-01-01', $this->experianCrosscoreFraudRequestDTO->dob());
    }

    public function testAddress(): void
    {
        $this->assertEquals($this->data['address'], $this->experianCrosscoreFraudRequestDTO->address());
    }
    public function testArray(): void
    {
        $this->assertEquals($this->data, $this->experianCrosscoreFraudRequestDTO->toArray());
    }
}
