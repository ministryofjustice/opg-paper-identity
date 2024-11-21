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
        $result = $matchLpaActor->checkMatch($lpasData, $firstName, $lastName, $dob);
        $this->assertEqualsCanonicalizing($expected_result, $result);
    }

    public static function nameLpaData(): array
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
}
