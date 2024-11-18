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
    public function testCheckNameMatch(
        string $firstName,
        string $lastName,
        array $lpasData,
        array $expected_result
    ): void {
        $matchLpaActor = new VoucherMatchLpaActorHelper();
        $result = $matchLpaActor->checkNameMatch($firstName, $lastName, $lpasData);
        $this->assertEquals($expected_result, $result);
    }

    public static function nameLpaData(): array
    {
        return [
            [
                "firstName" => "firstname",
                "lastName" => "lastname",
                "lpasData" => [
                ],
                "expected_result" => []
            ],
            [
                "firstName" => "firstname",
                "lastName" => "lastname",
                "lpasData" => [
                    "opg.poas.lpastore" => [
                        "donor" => [
                            "firstNames" => "firstname",
                            "lastName" => "lastname"
                        ],
                        "certificateProvider" => [
                            "firstNames" => "FIRSTNAME",
                            "lastName" => "LASTNAME"
                        ],
                    ]
                ],
                "expected_result" => [
                    LpaActorTypes::DONOR->value,
                    LpaActorTypes::CP->value,
                ]
            ],
            [
                "firstName" => "firstname",
                "lastName" => "lastname",
                "lpasData" => [
                    "opg.poas.lpastore" => [
                        "attorneys" => [
                            [
                                "status" => "active",
                                "firstNames" => "differentname",
                                "lastName" => "lastname"
                            ],
                            [
                                "status" => "active",
                                "firstNames" => "firstname",
                                "lastName" => "lastname"
                            ],
                            [
                                "status" => "active",
                                "firstNames" => "firstname",
                                "lastName" => "differentname"
                            ],
                            [
                                "status" => "replacement",
                                "firstNames" => "firstname",
                                "lastName" => "lastname"
                            ],
                        ]
                    ],
                ],
                "expected_result" => [
                    LpaActorTypes::ATTORNEY->value,
                    LpaActorTypes::R_ATTORNEY->value,
                ],
            ],
            [
                "firstName" => "firstname",
                "lastName" => "lastname",
                "lpasData" => [
                    "opg.poas.sirius" => [
                        "donor" => [
                            "firstname" => "firstname",
                            "surname" => "lastname"
                        ],
                    ],
                ],
                "expected_result" => [
                    LpaActorTypes::DONOR->value,
                ]
            ],
        ];
    }
}
