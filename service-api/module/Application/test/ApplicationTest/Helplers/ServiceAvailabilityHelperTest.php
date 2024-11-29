<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Helpers\ServiceAvailabilityHelper;
use Application\Model\Entity\CaseData;
use ApplicationTest\TestCase;

class ServiceAvailabilityHelperTest extends TestCase
{
    /**
     * @dataProvider data
     */
    public function testProcessServicesWithCaseData(
        array $config,
        array $caseData,
        array $services,
        array $expected
    ): void {
        $case = CaseData::fromArray($caseData);
        $helper = new ServiceAvailabilityHelper($services, $case, $config);

        $this->assertEquals($helper->processServicesWithCaseData(), $expected);
    }

    public static function data(): array
    {
        $config = [
            'opg_settings' => [
                'identity_documents' => [
                    'PASSPORT' => "Passport",
                    'DRIVING_LICENCE' => 'Driving licence',
                    'NATIONAL_INSURANCE_NUMBER' => 'National Insurance number',
                ],
                'identity_methods' => [
                    'POST_OFFICE' => 'Post Office',
                    'VOUCHING' => 'Have someone vouch for the identity of the donor',
                    'COURT_OF_PROTECTION' => 'Court of protection',
                ],
                'identity_services' => [
                    'EXPERIAN' => 'Experian',
                ],
                'banner_messages' => [
                    'donor' => [
                        'NODECISION' => 'The donor cannot ID over the phone due to a lack of ' .
                            'available security questions or failure to answer them correctly on a previous occasion.',
                        'STOP' => 'The donor cannot ID over the phone or have someone vouch for them due to a lack ' .
                            'of available information from Experian or a failure to answer the security questions ' .
                            'correctly on a previous occasion.'
                    ],
                    'certificateProvider' => [
                        'NODECISION' => 'The certificate provider cannot ID over the phone due to a lack of ' .
                            'available information from Experian or a failure to answer the security questions ' .
                            'correctly on a previous occasion.',
                        'STOP' => 'The certificate provider cannot ID over the phone due to a lack of ' .
                            'available information from Experian or a failure to answer the security ' .
                            'questions correctly on a previous occasion.',
                    ]
                ],
            ]
        ];

        $services = [
            'EXPERIAN' => true,
            'PASSPORT' => true,
            'DRIVING_LICENCE' => true,
            'NATIONAL_INSURANCE_NUMBER' => true,
            'POST_OFFICE' => true
        ];

        $servicesPostOfficeDown = [
            'EXPERIAN' => true,
            'PASSPORT' => true,
            'DRIVING_LICENCE' => true,
            'NATIONAL_INSURANCE_NUMBER' => true,
            'POST_OFFICE' => false
        ];

        $servicesExperianDown = [
            'EXPERIAN' => false,
            'PASSPORT' => true,
            'DRIVING_LICENCE' => true,
            'NATIONAL_INSURANCE_NUMBER' => true,
            'POST_OFFICE' => true
        ];

        $servicesPassportDown = [
            'EXPERIAN' => true,
            'PASSPORT' => false,
            'DRIVING_LICENCE' => true,
            'NATIONAL_INSURANCE_NUMBER' => true,
            'POST_OFFICE' => true
        ];

        $expected = [
            'data' => [
                'EXPERIAN' => true,
                'PASSPORT' => true,
                'DRIVING_LICENCE' => true,
                'NATIONAL_INSURANCE_NUMBER' => true,
                'POST_OFFICE' => true,
                'VOUCHING' => true,
                'COURT_OF_PROTECTION' => true,
            ],
            'messages' => []
        ];

        $expectedNoDec = [
            'data' => [
                'PASSPORT' => false,
                'DRIVING_LICENCE' => false,
                'NATIONAL_INSURANCE_NUMBER' => false,
                'POST_OFFICE' => true,
                'VOUCHING' => true,
                'COURT_OF_PROTECTION' => true,
                'EXPERIAN' => false,
            ],
            'messages' => [
                'banner' => 'The donor cannot ID over the phone due to a lack of ' .
                    'available security questions or failure to answer them correctly on a previous occasion.',
            ]
        ];

        $expectedStop = [
            'data' => [
                'PASSPORT' => false,
                'DRIVING_LICENCE' => false,
                'NATIONAL_INSURANCE_NUMBER' => false,
                'POST_OFFICE' => true,
                'VOUCHING' => false,
                'COURT_OF_PROTECTION' => true,
                'EXPERIAN' => false,
            ],
            'messages' => [
                'banner' => 'The donor cannot ID over the phone or have someone vouch for them due to a lack of ' .
                    'available information from Experian or a failure to answer the security questions correctly ' .
                    'on a previous occasion.'
            ]
        ];

        $expectedKbvFail = [
            'data' => [
                'PASSPORT' => false,
                'DRIVING_LICENCE' => false,
                'NATIONAL_INSURANCE_NUMBER' => false,
                'POST_OFFICE' => true,
                'VOUCHING' => true,
                'COURT_OF_PROTECTION' => true,
                'EXPERIAN' => false,
            ],
            'messages' => [
                'banner' => 'The donor cannot ID over the phone due to a lack of ' .
                    'available security questions or failure to answer them correctly on a previous occasion.',
            ]
        ];

        $case = [
            "id" => "b6aa3ee6-cd06-42b0-82c3-77051a4a4e34",
            "personType" => "donor",
            "claimedIdentity" => [
                "firstName" => "Lee",
                "lastName" => "Manthrope",
                "dob" => "1986-09-03",
                "address" => [
                    "line1" => "18 BOURNE COURT",
                    "line2" => "",
                    "line3" => "",
                    "town" => "Southamption",
                    "postcode" => "SO15 3AA",
                    "country" => "GB"
                ]
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3"
            ],
            "documentComplete" => true,
            "identityCheckPassed" => true,
            "professionalAddress" => [
            ],
            "searchPostcode" => null,
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "kbvQuestions" => [
                [
                    "externalId" => "Q00007",
                    "question" => "Which company provides your car insurance?",
                    "prompts" => [
                        "ShieldSafe",
                        "Guardian Drive Assurance",
                        "SafeDrive Insurance",
                        "Swift Cover Protection"
                    ],
                    "answered" => false
                ],
                [
                    "externalId" => "Q00003",
                    "question" => "What is your mother’s maiden name?",
                    "prompts" => [
                        "Germanotta",
                        "Blythe",
                        "Gumm",
                        "Micklewhite"
                    ],
                    "answered" => false
                ]
            ],
            "idMethodIncludingNation" => [
                "id_method" => "PASSPORT",
                "id_route" => "TELEPHONE",
                "id_country" => "GBR"
            ],
            "iiqControl" => [
                "urn" => "b6aa3ee6-cd06-42b0-82c3-77051a4a4e34",
                "authRefNo" => "6B3TGRWSKC"
            ],
            "fraudScore" => [
                "decision" => "ACCEPT",
                "score" => 265
            ]
        ];

        $caseNoDecision = array_merge($case, [
            'fraudScore' => [
                "decision" => "NODECISION",
                "score" => 0
            ]
        ]);

        $caseStop = array_merge($case, [
            'fraudScore' => [
                "decision" => "STOP",
                "score" => 999
            ]
        ]);

        $caseKbvFail = array_merge($case, [
            "identityCheckPassed" => false
        ]);

        return [
            [
                $config,
                $case,
                $services,
                $expected
            ],
            [
                $config,
                $caseNoDecision,
                $services,
                $expectedNoDec
            ],
            [
                $config,
                $caseStop,
                $services,
                $expectedStop
            ],
            [
                $config,
                $caseKbvFail,
                $services,
                $expectedKbvFail
            ],
            [
                $config,
                $case,
                $servicesPostOfficeDown,
                array_merge($expected, [
                    'data' => [
                        'PASSPORT' => true,
                        'DRIVING_LICENCE' => true,
                        'NATIONAL_INSURANCE_NUMBER' => true,
                        'POST_OFFICE' => false,
                        'VOUCHING' => true,
                        'COURT_OF_PROTECTION' => true,
                        'EXPERIAN' => true,
                    ]
                ])
            ],
            [
                $config,
                $case,
                $servicesExperianDown,
                [
                    'data' => [
                        'PASSPORT' => false,
                        'DRIVING_LICENCE' => false,
                        'NATIONAL_INSURANCE_NUMBER' => false,
                        'POST_OFFICE' => true,
                        'VOUCHING' => true,
                        'COURT_OF_PROTECTION' => true,
                        'EXPERIAN' => false,
                    ],
                    'messages' => [
                        'service_status' =>
                        'Online identity verification is not presently available',
                    ]
                ]
            ],
            [
                $config,
                $case,
                $servicesPassportDown,
                [
                    'data' => [
                        'PASSPORT' => false,
                        'DRIVING_LICENCE' => true,
                        'NATIONAL_INSURANCE_NUMBER' => true,
                        'POST_OFFICE' => true,
                        'VOUCHING' => true,
                        'COURT_OF_PROTECTION' => true,
                        'EXPERIAN' => true,
                    ],
                    'messages' => [
                        'service_status' =>
                        'Some identity verification methods are not presently available',
                    ]
                ]
            ]
        ];
    }
}
