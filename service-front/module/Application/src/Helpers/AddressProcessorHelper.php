<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Contracts\OpgApiServiceInterface;

/**
 * @psalm-import-type Address from OpgApiServiceInterface
 */
class AddressProcessorHelper
{
    public static array $addressTypes = [
        'lpaStoreAddressType' => [
            'line1' => 'line1',
            'line2' => 'line2',
            'line3' => 'line3',
            'town' => 'town',
            'postcode' => 'postcode',
            'country' => 'country',
        ],
        'siriusAddressType' => [
            'line1' => 'addressLine1',
            'line2' => 'addressLine2',
            'line3' => 'addressLine3',
            'town' => 'town',
            'postcode' => 'postcode',
            'country' => 'country',
        ]
    ];

    public const ERROR_POSTCODE_NOT_FOUND = 'The entered postcode could not be found. Please try a valid postcode.';

    public function __construct()
    {
    }

    /**
     * @return Address
     */
    public function getAddress(array $address): array
    {
        return [
            'line1' => $address['line1'] ?? '',
            'line2' => $address['line2'] ?? '',
            'line3' => $address['line3'] ?? '',
            'town' => $address['town'] ?? '',
            'postcode' => $address['postcode'] ?? '',
            'country' => $address['country'] ?? '',
        ];
    }

    /**
     * @param mixed $address
     * @return string[]
     */
    public static function processAddress(array $address, string $addressType): array
    {
        $processedAddress = [];

        foreach (self::$addressTypes[$addressType] as $key => $value) {
            $processedAddress[$key] = array_key_exists($value, $address) ? $address[$value] : '';
        }

        return $processedAddress;
    }

    public static function stringifyAddresses(array $addresses): array
    {
        $stringified = [];

        foreach ($addresses as $arr) {
            if (array_key_exists('description', $arr)) {
                unset($arr['description']);     // this field comes back from the mock at present
            }
            $string = function (array $arr): string {
                $str = '';
                foreach ($arr as $line) {
                    if (strlen($line) > 0) {
                        $str .= trim($line) . ", ";
                    }
                }

                return $str;
            };
            $index = json_encode($arr);

            $arrString = $string($arr);

            $stringified[$index] = substr(
                $arrString,
                0,
                strlen($arrString) - 2
            );
        }

        return $stringified;
    }
}
