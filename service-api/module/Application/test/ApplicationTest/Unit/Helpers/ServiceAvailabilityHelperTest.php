<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Helpers;

use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Helpers\ServiceAvailabilityHelper;
use Application\Model\Entity\CaseData;
use Dom\Document;
use LanguageServerProtocol\DocumentRangeFormattingClientCapabilities;
use PhpParser\Comment\Doc;
use PHPUnit\Framework\TestCase;

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
                    DocumentType::Passport->value => "Passport",
                    DocumentType::DrivingLicence->value => 'Driving licence',
                    DocumentType::NationalInsuranceNumber->value => 'National Insurance number',
                ],
                'identity_routes' => [
                    IdRoute::POST_OFFICE->value => 'Post Office',
                    IdRoute::VOUCHING->value => 'Have someone vouch for the identity of the donor',
                    IdRoute::COURT_OF_PROTECTION->value => 'Court of protection',
                ],
                'identity_services' => [
                    IdRoute::TELEPHONE->value => 'Experian',
                ],
                'banner_messages' => [
                    'NODECISION' => 'The donor cannot ID over the phone due to a lack of available security ' .
                        'questions or failure to answer them correctly on a previous occasion.',
                    'DONOR_STOP' => 'The donor cannot ID over the phone or have someone vouch for them due to a lack ' .
                        'of available information from Experian or a failure to answer the security questions ' .
                        'correctly on a previous occasion.',
                    'CP_STOP' => 'The certificate provider cannot ID over the phone due to a lack of available ' .
                        'information from Experian or a failure to answer the security questions correctly on a ' .
                        'previous occasion.',
                    'VOUCHER_STOP' => 'The person vouching cannot ID over the phone due to a lack of available ' .
                        'information from Experian or a failure to answer the security questions correctly ' .
                        'on a previous occasion.',
                    'LOCKED_ID_SUCCESS' => 'The %s has already proved their identity over the ' .
                        'phone with a valid document',
                    'LOCKED' => 'The %s cannot prove their identity over the phone because they have tried before ' .
                        'and their details did not match the document provided.',
                    'LOCKED_SUCCESS' => 'The %s has already confirmed their identity. The %s has already ' .
                        'completed an ID check for this LPA',
                ],
                'person_type_labels' => [
                    'donor' => 'donor',
                    'certificateProvider' => 'certificate provider',
                    'voucher' => 'person vouching'
                ]
            ]
        ];

        $services = [
            IdRoute::TELEPHONE->value => true,
            DocumentType::Passport->value => true,
            DocumentType::DrivingLicence->value => true,
            DocumentType::NationalInsuranceNumber->value => true,
            IdRoute::POST_OFFICE->value => true
        ];

        $servicesPostOfficeDown = array_merge(
            $services,
            [IdRoute::POST_OFFICE->value => false]
        );

        $servicesExperianDown = array_merge(
            $services,
            [IdRoute::TELEPHONE->value => false]
        );

        $servicesPassportDown = array_merge(
            $services,
            [DocumentType::Passport->value => false]
        );

        $allTrue = [
            IdRoute::TELEPHONE->value => true,
            DocumentType::Passport->value => true,
            DocumentType::DrivingLicence->value => true,
            DocumentType::NationalInsuranceNumber->value => true,
            IdRoute::POST_OFFICE->value => true,
            IdRoute::VOUCHING->value => true,
            IdRoute::COURT_OF_PROTECTION->value => true,
        ];

        $allFalse = [
            IdRoute::TELEPHONE->value => false,
            DocumentType::Passport->value => false,
            DocumentType::DrivingLicence->value => false,
            DocumentType::NationalInsuranceNumber->value => false,
            IdRoute::POST_OFFICE->value => false,
            IdRoute::VOUCHING->value => false,
            IdRoute::COURT_OF_PROTECTION->value => false,
        ];

        $expected = [
            'data' => $allTrue,
            'messages' => [],
            'additional_restriction_messages' => [],
        ];

        $expectedNoDec = [
            'data' => array_merge(
                $allFalse,
                [
                    IdRoute::POST_OFFICE->value => true,
                    IdRoute::VOUCHING->value => true,
                    IdRoute::COURT_OF_PROTECTION->value => true,
                ]
            ),
            'messages' => [
                'banner' => 'The donor cannot ID over the phone due to a lack of ' .
                    'available security questions or failure to answer them correctly on a previous occasion.',
            ],
            'additional_restriction_messages' => [],
        ];

        $expectedStop = [
            'data' => array_merge(
                $allFalse,
                [
                    IdRoute::POST_OFFICE->value => true,
                    IdRoute::COURT_OF_PROTECTION->value => true,
                ]
            ),
            'messages' => [
                'banner' => 'The donor cannot ID over the phone or have someone vouch for them due to a lack of ' .
                    'available information from Experian or a failure to answer the security questions correctly ' .
                    'on a previous occasion.'
            ],
            'additional_restriction_messages' => [],
        ];

        $expectedKbvFail = [
            'data' => array_merge(
                $allFalse,
                [
                    IdRoute::POST_OFFICE->value => true,
                    IdRoute::VOUCHING->value => true,
                    IdRoute::COURT_OF_PROTECTION->value => true,
                ]
            ),
            'messages' => [
                'banner' => 'The donor cannot ID over the phone or have someone vouch for them due to a lack of ' .
                    'available information from Experian or a failure to answer the security questions correctly ' .
                    'on a previous occasion.',
            ],
            'additional_restriction_messages' => [],
        ];

        $expectedKbvEmpty = [
            'data' => array_merge(
                $allFalse,
                [
                    IdRoute::POST_OFFICE->value => true,
                    IdRoute::VOUCHING->value => true,
                    IdRoute::COURT_OF_PROTECTION->value => true,
                ]
            ),
            'messages' => [
                'banner' => 'The donor cannot ID over the phone due to a lack of ' .
                    'available security questions or failure to answer them correctly ' .
                    'on a previous occasion.',
            ],
            'additional_restriction_messages' => [],
        ];

        $expectedDocSuccess = [
            'data' => array_merge(
                $allFalse,
                [
                    IdRoute::POST_OFFICE->value => true,
                    IdRoute::VOUCHING->value => true,
                    IdRoute::COURT_OF_PROTECTION->value => true,
                ]
            ),
            'messages' => [
                'banner' => 'The donor has already proved their identity over the ' .
                    'phone with a valid document',
            ],
            'additional_restriction_messages' => [],
        ];

        $case = [
            "id" => "4d41c926-d11c-4341-8500-b36a666a35dd",
            "personType" => "donor",
            "lpas" => [
                "M-XYXY-YAGA-35G3"
            ],
            "documentComplete" => false,
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "idMethod" => [
                "doc_type" => DocumentType::DrivingLicence->value,
                "id_route" => IdRoute::TELEPHONE->value,
                "id_country" => "GBR"
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
            "identityCheckPassed" => false,
            "caseProgress" => [
                "kbvs" => [
                    "result" => false
                ]
            ]
        ]);

        $caseKbvEmpty = array_merge($case, [
            "identityIQ" => [
                "kbvQuestions" => [],
                "iiqControl" => [
                    "urn" => "******",
                    "authRefNo" => "********"
                ],
                "thinfile" => true
            ]
        ]);

        $caseDocChecked = array_merge($case, [
            "caseProgress" => [
                "abandonedFlow" => null,
                "docCheck" => [
                    "idDocument" => DocumentType::DrivingLicence->value,
                    "state" => true
                ],
                "kbvs" => null,
                "fraudScore" => [
                    "decision" => "ACCEPT",
                    "score" => 265
                ]
            ],
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
                $caseKbvEmpty,
                $services,
                $expectedKbvEmpty
            ],
            [
                $config,
                $caseDocChecked,
                $services,
                $expectedDocSuccess
            ],
            [
                $config,
                $case,
                $servicesPostOfficeDown,
                array_merge(
                    $expected,
                    ['data' => array_merge($allTrue, [IdRoute::POST_OFFICE->value => false])]
                )
            ],
            [
                $config,
                $case,
                $servicesExperianDown,
                [
                    'data' => array_merge(
                        $allFalse,
                        [
                            IdRoute::POST_OFFICE->value => true,
                            IdRoute::VOUCHING->value => true,
                            IdRoute::COURT_OF_PROTECTION->value => true,
                        ]
                    ),
                    'messages' => [
                        'service_status' =>
                        'Online identity verification is not presently available',
                    ],
                    'additional_restriction_messages' => [],
                ]
            ],
            [
                $config,
                $case,
                $servicesPassportDown,
                [
                    'data' => array_merge($allTrue, [DocumentType::Passport->value => false]),
                    'messages' => [
                        'service_status' =>
                        'Some identity verification methods are not presently available',
                    ],
                    'additional_restriction_messages' => [],
                ]
            ]
        ];
    }
}
