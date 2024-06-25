<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Helpers\AddressProcessorHelper;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
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
    public function testGetAddress(
        array $unprocessedAddress,
        array $expectedAddress
    ): void {

        $addressProcessorHelper = new AddressProcessorHelper();
        $processed = $addressProcessorHelper->getAddress($unprocessedAddress);

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


    /**
     * @dataProvider processAddressData
     */
    public function testProcessAddressData(
        array $address,
        string $addressType,
        array $expected
    ): void {
        $addressProcessorHelper = new AddressProcessorHelper();

        $response = $addressProcessorHelper->processAddress($address, $addressType);

        $this->assertEquals($expected, $response);
    }

    public static function processAddressData(): array
    {
        return [
            [
                [
                    'addressLine1' => "Park House",
                    'addressLine2' => 'Park Lane',
                    'town' => 'London',
                    'postcode' => 'SW1A 1AA',
                    'country' => 'UK'
                ],
                'siriusAddressType',
                [
                    'line1' => "Park House",
                    'line2' => 'Park Lane',
                    'line3' => '',
                    'town' => 'London',
                    'postcode' => 'SW1A 1AA',
                    'country' => 'UK'
                ]
            ],
            [
                [
                    'line1' => "Park House",
                    'line3' => 'Park Lane',
                    'town' => 'London',
                    'postcode' => 'SW1A 1AA',
                    'country' => 'UK'
                ],
                'lpaStoreAddressType',
                [
                    'line1' => "Park House",
                    'line2' => '',
                    'line3' => 'Park Lane',
                    'town' => 'London',
                    'postcode' => 'SW1A 1AA',
                    'country' => 'UK'
                ]
            ],
        ];
    }
}
