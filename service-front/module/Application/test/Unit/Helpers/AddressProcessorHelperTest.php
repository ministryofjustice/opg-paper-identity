<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers;

use Application\Helpers\AddressProcessorHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AddressProcessorHelperTest extends TestCase
{
    #[DataProvider('addressData')]
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


    #[DataProvider('processAddressData')]
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


    #[DataProvider('stringifyAddressesData')]
    public function testStringifyAddresses(
        array $addresses,
        array $expected,
        string $json
    ): void {
        $addressProcessorHelper = new AddressProcessorHelper();

        $response = $addressProcessorHelper->stringifyAddresses($addresses);

        $this->assertEquals($expected[0], $response[$json]);
    }

    public static function stringifyAddressesData(): array
    {
        $address = [
            'line1' => "Park House",
            'line2' => '',
            'line3' => 'Park Lane',
            'town' => 'London',
            'postcode' => 'SW1A 1AA',
            'country' => 'UK'
        ];

        $json = is_null(json_encode($address)) ? "" : json_encode($address);

        return [
            [
                [
                    $address
                ],
                [
                    "Park House, Park Lane, London, SW1A 1AA, UK"
                ],
                $json
            ],
        ];
    }
}
