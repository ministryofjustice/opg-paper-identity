<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Enums\LpaActorTypes;
use PHPUnit\Framework\TestCase;

class VoucherMatchLpaActorHelperTest extends TestCase
{
    /**
     * @dataProvider nameDobLpaData
     */
    public function testCheckMatch(
        string $firstName,
        string $lastName,
        ?string $dob,
        array $lpasData,
        array $expected_result
    ): void {
        $matchLpaActor = new VoucherMatchLpaActorHelper();
        $result = $matchLpaActor->checkMatch($lpasData, $firstName, $lastName, $dob);
        $this->assertEqualsCanonicalizing($expected_result, $result);
    }

    /**
     * @dataProvider addressLpaData
     */
    public function testCheckAddressDonorMatch(array $lpasData, array $address, bool $expected_result): void
    {
        $matchLpaActor = new VoucherMatchLpaActorHelper();
        $result = $matchLpaActor->checkAddressDonorMatch($lpasData, $address);
        $this->assertEquals($expected_result, $result);
    }

    public static function nameDobLpaData(): array
    {
        $lpaDataLpaStore = [
            "donor" => [
                "firstNames" => "donorfirstname",
                "lastName" => "donorlastname",
                "dateOfBirth" => "1980-01-01",
            ],
            "certificateProvider" => [
                "firstNames" => "certificateProviderfirstname",
                "lastName" => "certificateProviderlastname",
                "dateOfBirth" => "1990-01-01",
            ],
            "attorneys" => [
                [
                    "status" => "active",
                    "firstNames" => "attorneyfirstname",
                    "lastName" => "attorneylastname",
                    "dateOfBirth" => "1980-01-01",
                ],
                [
                    "status" => "active",
                    "firstNames" => "attorneyfirstname",
                    "lastName" => "attorneylastname",
                    "dateOfBirth" => "1990-01-01",
                ],
                [
                    "status" => "active",
                    "firstNames" => "differentAttorneyfirstname",
                    "lastName" => "differentAttorneylastname",
                    "dateOfBirth" => "1980-01-01",
                ],
                [
                    "status" => "replacement",
                    "firstNames" => "replacementAttorneyfirstname",
                    "lastName" => "replacementAttorneylastname",
                    "dateOfBirth" => "1990-01-01",
                ],
            ],
        ];

        $lpaDataSirius = ["donor" => [
                "firstname" => "firstname",
                "surname" => "lastname",
                "dob" => "01/01/1980",
            ]
        ];

        return [
            [
                "firstName" => "firstname",
                "lastName" => "lastname",
                "dob" => null,
                "lpasData" => [
                    "opg.poas.sirius" => [],
                ],
                "expected_result" => []
            ],
            [
                "firstName" => "donorfirstname",
                "lastName" => "donorlastname",
                "dob" => null,
                "lpasData" => [
                    "opg.poas.sirius" => $lpaDataSirius,
                    "opg.poas.lpastore" => $lpaDataLpaStore,
                ],
                "expected_result" => [
                    [
                        "firstName" => "donorfirstname",
                        "lastName" => "donorlastname",
                        "dob" => "1980-01-01",
                        "type" => LpaActorTypes::DONOR->value,
                    ],
                ]
            ],
            [
                "firstName" => "donorfirstname",
                "lastName" => "donorlastname",
                "dob" => "1990-01-01",
                "lpasData" => [
                    "opg.poas.sirius" => $lpaDataSirius,
                    "opg.poas.lpastore" => $lpaDataLpaStore,
                ],
                "expected_result" => []
            ],
            [
                "firstName" => "attorneyfirstname",
                "lastName" => "attorneylastname",
                "dob" => null,
                "lpasData" => [
                    "opg.poas.sirius" => $lpaDataSirius,
                    "opg.poas.lpastore" => $lpaDataLpaStore,
                ],
                "expected_result" => [
                    [
                        "firstName" => "attorneyfirstname",
                        "lastName" => "attorneylastname",
                        "dob" => "1980-01-01",
                        "type" => LpaActorTypes::ATTORNEY->value,
                    ],
                    [
                        "firstName" => "attorneyfirstname",
                        "lastName" => "attorneylastname",
                        "dob" => "1990-01-01",
                        "type" => LpaActorTypes::ATTORNEY->value,
                    ],
                ],
            ],
            [
                "firstName" => "attorneyfirstname",
                "lastName" => "attorneylastname",
                "dob" => "1990-1-1",
                "lpasData" => [
                    "opg.poas.sirius" => $lpaDataSirius,
                    "opg.poas.lpastore" => $lpaDataLpaStore,
                ],
                "expected_result" => [
                    [
                        "firstName" => "attorneyfirstname",
                        "lastName" => "attorneylastname",
                        "dob" => "1990-01-01",
                        "type" => LpaActorTypes::ATTORNEY->value,
                    ],
                ],
            ],
            [
                "firstName" => "replacementAttorneyfirstname",
                "lastName" => "replacementAttorneylastname",
                "dob" => null,
                "lpasData" => [
                    "opg.poas.sirius" => $lpaDataSirius,
                    "opg.poas.lpastore" => $lpaDataLpaStore,
                ],
                "expected_result" => [
                    [
                        "firstName" => "replacementAttorneyfirstname",
                        "lastName" => "replacementAttorneylastname",
                        "dob" => "1990-01-01",
                        "type" => LpaActorTypes::R_ATTORNEY->value,
                    ],
                ],
            ],
            [
                "firstName" => "firstname",
                "lastName" => "lastname",
                "dob" => "1980-01-01",
                "lpasData" => [
                    "opg.poas.sirius" => $lpaDataSirius,
                ],
                "expected_result" => [
                    [
                        "firstName" => "firstname",
                        "lastName" => "lastname",
                        "dob" => "01/01/1980",
                        "type" => LpaActorTypes::DONOR->value,
                    ],
                ],
            ],
        ];
    }

    public static function addressLpaData(): array
    {

        $addressOne = [
            'line1' => '123 Fake Street',
            'line2' => '',
            'line3' => '',
            'town' => 'Faketown',
            'postcode' => 'FA2 3KE',
            'country' => 'UK',
        ];

        $addressOneSirius = [
            'addressLine1' => '123 FAKE STREET  ',
            'town' => 'Faketown',
            'postcode' => 'FA2 3KE',
            'country' => 'UK',
        ];

        $addressTwo = [
            'line1' => ' 456 Pretend Road',
            'line2' => 'Notrealshire',
            'town' => 'Faketown',
            'postcode' => 'FA9 3KE',
            'country' => 'UK'
        ];

        $addressTwoSirius = [
            'addressLine1' => '456 Pretend Road',
            'addressLine2' => 'Notrealshire',
            'town' => 'Faketown',
            'postcode' => 'FA9 3KE',
            'country' => 'UK',
        ];

        return [
            [
                "lpasData" => [],
                "address" => $addressOne,
                "expected_result" => false
            ],
            [
                "lpasData" => [
                    "opg.poas.lpastore" => ["donor" => ["address" => $addressOne]],
                    "opg.poas.sirius" => ["donor" => $addressTwoSirius],
                ],
                "address" => $addressOne,
                "expected_result" => true
            ],
            [
                "lpasData" => [
                    "opg.poas.sirius" => ["donor" => $addressOneSirius]],
                "address" => $addressOne,
                "expected_result" => true
            ],
            [
                "lpasData" => ["opg.poas.lpastore" => ["donor" => ["address" => $addressOne]]],
                "address" => $addressTwo,
                "expected_result" => false
            ],
        ];
    }
}
