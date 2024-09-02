<?php

declare(strict_types=1);

namespace ApplicationTest\PostOffice;

use PHPUnit\Framework\TestCase;
use Application\PostOffice\Country;

class CountryTest extends TestCase
{
    /**
     * @dataProvider countryEUOrEEAProvider
     */
    public function testIsEUOrEEA(
        Country $country,
        bool $expectedEUOrEEA,
    ): void {
        $this->assertEquals($expectedEUOrEEA, $country->isEUOrEEA());
    }

    public static function countryEUOrEEAProvider(): array
    {
        return [
            [Country::AUS, false],
            [Country::AUT, true],
            [Country::ISL, true],
            [Country::VEN, false],
        ];
    }
}
