<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Helpers\AddressProcessorHelper;
use PHPUnit\Framework\TestCase;

class AddressProcessorHelperTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider addressData
     */
    public function testAddressHelper(
        array $unprocessedAddress, $expectedAddress
    ): void {

        $addressProcessorHelper = new AddressProcessorHelper($unprocessedAddress);
        $processed = $addressProcessorHelper->getAddress();

        $this->assertEquals($expectedAddress, $processed);
    }


    public static function addressData(): array
    {
        return [
            [
                [
                    'country' => 'DD',
                    'line1' => '1 Street',
                    'line3' => 'D Estate',
                    'postcode' => 'LA1 2XN',
                    'town' => 'Middleton'
                ],
                [
                    'line1' => '1 Street',
                    'line2' => '',
                    'line3' => 'D Estate',
                    'town' => 'Middleton',
                    'postcode' => 'LA1 2XN',
                    'country' => 'DD',
                ]
            ],
            [
                [
                    'postcode' => 'LA1 2XN',
                    'country' => 'UK',
                    'line1' => '1 Street',
                    'line2' => '',
                    'town' => 'Middleton'
                ],
                [

                    'line1' => '1 Street',
                    'line2' => '',
                    'line3' => '',
                    'town' => 'Middleton',
                    'postcode' => 'LA1 2XN',
                    'country' => 'UK',
                ]
            ]
        ];
    }
}
