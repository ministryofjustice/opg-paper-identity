<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\Experian\FraudApi\DTO;

use Application\Experian\Crosscore\FraudApi\DTO\AddressDTO;
use Application\Experian\Crosscore\FraudApi\DTO\RequestDTO;
use PHPUnit\Framework\TestCase;

class ExperianCrosscoreFraudRequestDTOTest extends TestCase
{
    private RequestDTO $experianCrosscoreFraudRequestDTO;

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

        $this->experianCrosscoreFraudRequestDTO = new RequestDTO(
            $this->data['firstName'],
            $this->data['lastName'],
            $this->data['dob'],
            new AddressDTO(
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
