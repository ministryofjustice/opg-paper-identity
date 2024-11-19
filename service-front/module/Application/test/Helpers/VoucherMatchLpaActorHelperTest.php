<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Enums\LpaActorTypes;
use PHPUnit\Framework\TestCase;

class VoucherMatchLpaActorHelperTest extends TestCase
{
    /**
     * @dataProvider nameLpaData
     */
    public function testCheckMatch(
        string $firstName,
        string $lastName,
        ?string $dob,
        array $lpasData,
        array $expected_result
    ): void {
        $matchLpaActor = new VoucherMatchLpaActorHelper();
        $result = $matchLpaActor->checkMatch( $lpasData, $firstName, $lastName, $dob);
        $this->assertEqualsCanonicalizing($expected_result, $result);
    }

    public static function nameLpaData(): array
    {
        $lpaDataLpaStore = ["opg.poas.lpastore" => [
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
        ]];

        $lpaDataSirius = ["opg.poas.sirius" => [
            "firstname" => "firstname",
            "surname" => "lastname",
            "dob" => "1980-01-01",
        ]];

        return [
            [
                "firstName" => "firstname",
                "lastName" => "lastname",
                "dob" => null,
                "lpasData" => [],
                "expected_result" => []
            ],
            [
                "firstName" => "donorfirstname",
                "lastName" => "donorlastname",
                "dob" => null,
                "lpasData" => $lpaDataLpaStore,
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
                "lpasData" => $lpaDataLpaStore,
                "expected_result" => []
            ],
            [
                "firstName" => "attorneyfirstname",
                "lastName" => "attorneylastname",
                "dob" => null,
                "lpasData" => $lpaDataLpaStore,
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
                "dob" => "1990-01-01",
                "lpasData" => $lpaDataLpaStore,
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
                "lpasData" => $lpaDataLpaStore,
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
                "dob" => null,
                "lpasData" => $lpaDataSirius,
                "expected_result" => [
                    [
                        "firstName" => "firstname",
                        "surname" => "lastname",
                        "dob" => "1980-01-01",
                        "type" => LpaActorTypes::DONOR->value,
                    ],
                ],
            ],
        ];
    }
}
