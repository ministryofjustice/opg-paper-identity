<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Experian\IIQ;

use Application\Enums\PersonType;
use Application\Experian\IIQ\ConfigBuilder;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\IdentityIQ;
use Application\Model\Entity\IIQControl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigBuilderTest extends TestCase
{
    #[DataProvider('saaConfigData')]
    public function testSAAFormat(array $case, array $expected): void
    {
        $caseData = CaseData::fromArray($case);

        $configBuilder = new ConfigBuilder();

        $saaConfig = $configBuilder->buildSAARequest($caseData);

        $this->assertEquals($expected, $saaConfig);
    }

    public static function saaConfigData(): array
    {
        return [
            [
                [
                    'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                    'claimedIdentity' => [
                        'firstName' => 'Maria',
                        'lastName' => 'Williams',
                        'dob' => '1960-01-01',
                        'address' => [
                            'line1' => '123 long street',
                            'line2' => 'Kings Cross',
                            'town' => 'London',
                            'postcode' => 'NW1 1SP',
                        ]
                    ],
                    'personType' => PersonType::Donor->value,
                    'caseProgress' => [
                        'fraudScore' => [
                            "decision" => "ACCEPT",
                            "score" => 265
                        ]
                    ]
                ],
                [
                    'Applicant' => [
                        'ApplicantIdentifier' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                        'Name' => [
                            'Title' => '',
                            'Forename' => 'Maria',
                            'Surname' => 'Williams',
                        ],
                        'DateOfBirth' => [
                            'CCYY' => '1960',
                            'MM' => '01',
                            'DD' => '01',
                        ],
                    ],
                    'ApplicationData' => [
                        'SearchConsent' => 'Y',
                    ],
                    'LocationDetails' => [
                        'LocationIdentifier' => '1',
                        'UKLocation' => [
                            'HouseName' => '123 long street',
                            'Street' => 'Kings Cross',
                            'District' => '',
                            'PostTown' => 'London',
                            'Postcode' => 'NW1 1SP',
                        ],
                    ],
                ]
            ],
            [
                [
                    'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                    'claimedIdentity' => [
                        'firstName' => 'Maria',
                        'lastName' => 'Williams',
                        'dob' => '1960-01-01',
                        'address' => [
                            'line1' => '123 long street',
                            'line2' => 'Kings Cross',
                            'town' => 'London',
                            'postcode' => 'NW1 1SP',
                        ]
                    ],
                    'personType' => PersonType::Donor->value,
                    'caseProgress' => [
                        'fraudScore' => [
                            "decision" => "STOP",
                            "score" => 990
                        ]
                    ]
                ],
                [
                    'Applicant' => [
                        'ApplicantIdentifier' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                        'Name' => [
                            'Title' => '',
                            'Forename' => 'Maria',
                            'Surname' => 'Williams',
                        ],
                        'DateOfBirth' => [
                            'CCYY' => '1960',
                            'MM' => '01',
                            'DD' => '01',
                        ],
                    ],
                    'ApplicationData' => [
                        'SearchConsent' => 'Y',
                        'Product' => '4 out of 4',
                    ],
                    'LocationDetails' => [
                        'LocationIdentifier' => '1',
                        'UKLocation' => [
                            'HouseName' => '123 long street',
                            'Street' => 'Kings Cross',
                            'District' => '',
                            'PostTown' => 'London',
                            'Postcode' => 'NW1 1SP',
                        ],
                    ],
                ]
            ],
            [
                [
                    'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                    'claimedIdentity' => [
                        'firstName' => 'Maria',
                        'lastName' => 'Williams',
                        'dob' => '1960-01-01',
                        'address' => [
                            'line1' => '123 long street',
                            'line2' => 'Kings Cross',
                            'town' => 'London',
                            'postcode' => 'NW1 1SP',
                        ]
                    ],
                    'personType' => PersonType::Donor->value,
                    'caseProgress' => [
                        'fraudScore' => [
                            "decision" => "REFER",
                            "score" => 950
                        ]
                    ]
                ],
                [
                    'Applicant' => [
                        'ApplicantIdentifier' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                        'Name' => [
                            'Title' => '',
                            'Forename' => 'Maria',
                            'Surname' => 'Williams',
                        ],
                        'DateOfBirth' => [
                            'CCYY' => '1960',
                            'MM' => '01',
                            'DD' => '01',
                        ],
                    ],
                    'ApplicationData' => [
                        'SearchConsent' => 'Y',
                        'Product' => '4 out of 4',
                    ],
                    'LocationDetails' => [
                        'LocationIdentifier' => '1',
                        'UKLocation' => [
                            'HouseName' => '123 long street',
                            'Street' => 'Kings Cross',
                            'District' => '',
                            'PostTown' => 'London',
                            'Postcode' => 'NW1 1SP',
                        ],
                    ],
                ]
            ]
        ];
    }

    public function testRTQFormat(): void
    {
        $configBuilder = new ConfigBuilder();

        $caseData = CaseData::fromArray([
            'identityIQ' => IdentityIQ::fromArray([
                'iiqControl' => IIQControl::fromArray([
                    'urn' => 'test UUID',
                    'authRefNo' => 'abc',
                ])
            ]),
            'caseProgress' => [
                'fraudScore' => [
                    "decision" => "ACCEPT",
                    "score" => 265
                ]
            ]
        ]);

        $rtqConfig = $configBuilder->buildRTQRequest([
            [
                'experianId' => 'QID21',
                'answer' => 'BASINGSTOKE',
                'flag' => 1,
            ],
        ], $caseData);

        $this->assertEquals([
            'Control' => [
                'URN' => 'test UUID',
                'AuthRefNo' => 'abc',
            ],
            'Responses' => [
                'Response' => [
                    [
                        'QuestionID' => 'QID21',
                        'AnswerGiven' => 'BASINGSTOKE',
                        'CustResponseFlag' => 1,
                        'AnswerActionFlag' => 'A',
                    ],
                ],
            ],
        ], $rtqConfig);
    }
}
