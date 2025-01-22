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

        $this->assertEquals($expected, $helper->processServicesWithCaseData());
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
                            'correctly on a previous occasion.',
                        'LOCKED' => 'The donor cannot prove their identity over the phone because they have ' .
                            'tried before and their details did not match the document provided.'
                    ],
                    'certificateProvider' => [
                        'NODECISION' => 'The certificate provider cannot ID over the phone due to a lack of ' .
                            'available information from Experian or a failure to answer the security questions ' .
                            'correctly on a previous occasion.',
                        'STOP' => 'The certificate provider cannot ID over the phone due to a lack of ' .
                            'available information from Experian or a failure to answer the security ' .
                            'questions correctly on a previous occasion.',
                        'LOCKED' => 'The certificate provider cannot prove their identity over the phone ' .
                            'because they have tried before and their details did not match the document provided.'
                    ],
                    'vouching' => [
                        'NODECISION' => 'The person vouching cannot ID over the phone due to a lack of ' .
                            'available information from Experian or a failure to answer the security questions ' .
                            'correctly on a previous occasion.',
                        'STOP' => 'The person vouching cannot ID over the phone due to a lack of ' .
                            'available information from Experian or a failure to answer the security ' .
                            'questions correctly on a previous occasion.',
                        'LOCKED' => 'The person vouching cannot prove their identity over the phone because they ' .
                            'have tried before and their details did not match the document provided.',
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
                'banner' => 'The donor cannot prove their identity over the phone because they have tried before and ' .
                    'their details did not match the document provided.',
            ]
        ];

        $case = [
            "id" => "4d41c926-d11c-4341-8500-b36a666a35dd",
            "idRoute" => "TELEPHONE",
            "personType" => "donor",
            "lpas" => [
                "M-XYXY-YAGA-35G3"
            ],
            "documentComplete" => false,
            "identityCheckPassed" => false,
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "idMethodIncludingNation" => [
                "id_method" => "DRIVING_LICENCE",
                "id_route" => "TELEPHONE",
                "id_country" => "GBR"
            ],
            "caseProgress" => [
                "abandonedFlow" => null,
                "docCheck" => [
                    "idDocument" => "DRIVING_LICENCE",
                    "state" => true
                ],
                "kbvs" => null,
                "fraudScore" => [
                    "decision" => "ACCEPT",
                    "score" => 265
                ]
            ],
            "claimedIdentity" => [
                "dob" => "1986-09-03",
                "firstName" => "Lee",
                "lastName" => "Manthrope",
                "address" => [
                    "postcode" => "SO15 3AA",
                    "country" => "GB",
                    "line3" => "",
                    "town" => "Southamption",
                    "line2" => "",
                    "line1" => "18 BOURNE COURT"
                ],
                "professionalAddress" => null
            ]
        ];

        $caseNoDecision = array_merge($case, [
            "caseProgress" => [
                "fraudScore" => [
                    "decision" => "NODECISION",
                    "score" => 0
                ]
            ]
        ]);

        $caseStop = array_merge($case, [
            "caseProgress" => [
                "fraudScore" => [
                    "decision" => "STOP",
                    "score" => 999
                ]
            ]
        ]);

        $caseKbvFail = array_merge($case, [
            "caseProgress" => [
                "kbvs" => [
                    "result" => false
                ]
            ]
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
