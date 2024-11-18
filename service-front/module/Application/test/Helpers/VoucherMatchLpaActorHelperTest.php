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
        $this->assertEquals($result, $expected_result);
    }

    public static function nameLpaData(): array
    {
        return [
            [
                "firstName" => "firstname",
                "lastName" => "lastname",
                "lpasData" => [
                ],
                "expected_result" => [
                    LpaActorTypes::DONOR->value => false,
                    LpaActorTypes::CP->value => false,
                    LpaActorTypes::ATTORNEY->value => false,
                    LpaActorTypes::R_ATTORNEY->value => false
                ]
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
                    LpaActorTypes::DONOR->value => true,
                    LpaActorTypes::CP->value => true,
                    LpaActorTypes::ATTORNEY->value => false,
                    LpaActorTypes::R_ATTORNEY->value => false
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
                    LpaActorTypes::DONOR->value => false,
                    LpaActorTypes::CP->value => false,
                    LpaActorTypes::ATTORNEY->value => true,
                    LpaActorTypes::R_ATTORNEY->value => true,
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
                    LpaActorTypes::DONOR->value => true,
                    LpaActorTypes::CP->value => false,
                    LpaActorTypes::ATTORNEY->value => false,
                    LpaActorTypes::R_ATTORNEY->value => false
                ]
            ],
        ];
    }
}
