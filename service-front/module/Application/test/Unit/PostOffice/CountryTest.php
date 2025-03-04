<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\PostOffice;

use Application\PostOffice\Country;
use PHPUnit\Framework\TestCase;

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
