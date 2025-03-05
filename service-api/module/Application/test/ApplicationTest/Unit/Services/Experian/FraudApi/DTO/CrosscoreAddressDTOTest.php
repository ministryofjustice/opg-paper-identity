<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\Experian\FraudApi\DTO;

use Application\Experian\Crosscore\FraudApi\DTO\AddressDTO;
use PHPUnit\Framework\TestCase;

class CrosscoreAddressDTOTest extends TestCase
{
    private AddressDTO $addressDTO;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'line1' => 'line1',
            'line2' => 'line2',
            'line3' => 'line3',
            'town' => 'town',
            'postcode' => 'postcode',
            'country' => 'country'
        ];

        $this->addressDTO = new AddressDTO(
            $this->data['line1'],
            $this->data['line2'],
            $this->data['line3'],
            $this->data['town'],
            $this->data['postcode'],
            $this->data['country']
        );
    }

    public function testLine1(): void
    {
        $this->assertEquals('line1', $this->addressDTO->line1());
    }

    public function testLine2(): void
    {
        $this->assertEquals('line2', $this->addressDTO->line2());
    }

    public function testLine3(): void
    {
        $this->assertEquals('line3', $this->addressDTO->line3());
    }

    public function testTown(): void
    {
        $this->assertEquals('town', $this->addressDTO->town());
    }

    public function testPostcode(): void
    {
        $this->assertEquals('postcode', $this->addressDTO->postcode());
    }

    public function testCountry(): void
    {
        $this->assertEquals('country', $this->addressDTO->country());
    }

    public function testArray(): void
    {
        $this->assertEquals($this->data, $this->addressDTO->toArray());
    }
}
