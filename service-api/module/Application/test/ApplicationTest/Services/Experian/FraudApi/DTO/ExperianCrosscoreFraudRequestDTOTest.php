<?php

declare(strict_types=1);

namespace ApplicationTest\Services\Experian\FraudApi\DTO;

use Application\Services\Experian\FraudApi\DTO\CrosscoreAddressDTO;
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
                'line1' => 'address_line_1',
                'line2' => 'address_line_2',
                'line3' => 'address_line_3',
                'town' => 'town',
                'postcode' => 'postcode',
                'country' => 'country',
            ]
        ];

        $this->experianCrosscoreFraudRequestDTO = new ExperianCrosscoreFraudRequestDTO(
            $this->data['firstName'],
            $this->data['lastName'],
            $this->data['dob'],
            new CrosscoreAddressDTO(
                $this->data['address']['line1'],
                $this->data['address']['line2'],
                $this->data['address']['line3'],
                $this->data['address']['town'],
                $this->data['address']['postcode'],
                $this->data['address']['country']
            )
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
        $this->assertEquals($this->data['address'], $this->experianCrosscoreFraudRequestDTO->address()->toArray());
    }
    public function testArray(): void
    {
        $this->assertEquals($this->data, $this->experianCrosscoreFraudRequestDTO->toArray());
    }
}
