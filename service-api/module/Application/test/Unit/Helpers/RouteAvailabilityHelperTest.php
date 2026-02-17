<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Helpers;

use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Enums\PersonType;
use Application\Helpers\RouteAvailabilityHelper;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RouteAvailabilityHelperTest extends TestCase
{
    #[DataProvider('data')]
    public function testProcessCase(
        array $config,
        array $caseData,
        array $externalServices,
        array $expected
    ): void {
        $case = CaseData::fromArray($caseData);
        $helper = new RouteAvailabilityHelper($externalServices, $config);
        $this->assertEquals($expected, $helper->processCase($case));
    }

    public static function data(): array
    {
        $config = [
            'opg_settings' => [
                'identity_documents' => [
                    DocumentType::Passport->value => 'Passport',
                    DocumentType::DrivingLicence->value => 'Driving licence',
                    DocumentType::NationalInsuranceNumber->value => 'National Insurance number',
                ],
                'identity_routes' => [
                    IdRoute::KBV->value => 'Experian',
                    IdRoute::POST_OFFICE->value => 'Post Office',
                    IdRoute::VOUCHING->value => 'Have someone vouch for the identity of the donor',
                    IdRoute::COURT_OF_PROTECTION->value => 'Court of protection',
                ],
                'banner_messages' => [
                    'DONOR_VOUCH_UNAVAILABLE' => 'donor-vouch-unavailable',
                    'LOCKED_EXPERIAN' => 'The %s has thinfile or failed KBVs',
                    'LOCKED_ID_FAILURE' => 'The %s failed doc-check',
                    'LOCKED_COMPLETE' => 'The identity check has already been completed',
                    'RESTRICTED_OPTIONS' => '%s could not be verified over the phone...'
                ],
            ]
        ];

        $externalServices = [
            IdRoute::KBV->value => true,
            DocumentType::Passport->value => true,
            DocumentType::DrivingLicence->value => true,
            DocumentType::NationalInsuranceNumber->value => true,
            IdRoute::POST_OFFICE->value => true
        ];

        $experianUnavailable = array_merge($externalServices, [IdRoute::KBV->value => false]);
        $postOfficeUnavailable = array_merge($externalServices, [IdRoute::POST_OFFICE->value => false]);
        $ninoUnavailable = array_merge($externalServices, [DocumentType::NationalInsuranceNumber->value => false]);
        $passportUnavailable = array_merge($externalServices, [DocumentType::Passport->value => false]);
        $drivingLicenceUnavailable = array_merge($externalServices, [DocumentType::DrivingLicence->value => false]);
        $allDocsUnavailable = array_merge(
            $externalServices,
            [
                DocumentType::NationalInsuranceNumber->value => false,
                DocumentType::Passport->value => false,
                DocumentType::DrivingLicence->value => false
            ]
        );

        $donorAllRoutesAvailable = [
            IdRoute::KBV->value => true,
            DocumentType::Passport->value => true,
            DocumentType::DrivingLicence->value => true,
            DocumentType::NationalInsuranceNumber->value => true,
            IdRoute::POST_OFFICE->value => true,
            IdRoute::VOUCHING->value => true,
            IdRoute::COURT_OF_PROTECTION->value => true,
        ];

        $otherAllRoutesAvailable = array_merge(
            $donorAllRoutesAvailable,
            [
                IdRoute::VOUCHING->value => false,
                IdRoute::COURT_OF_PROTECTION->value => false,
            ]
        );

        $allRoutesUnavailable = [
            IdRoute::KBV->value => false,
            DocumentType::Passport->value => false,
            DocumentType::DrivingLicence->value => false,
            DocumentType::NationalInsuranceNumber->value => false,
            IdRoute::POST_OFFICE->value => false,
            IdRoute::VOUCHING->value => false,
            IdRoute::COURT_OF_PROTECTION->value => false,
        ];

        $donorOffline = array_merge(
            $donorAllRoutesAvailable,
            [
                IdRoute::KBV->value => false,
                DocumentType::Passport->value => false,
                DocumentType::DrivingLicence->value => false,
                DocumentType::NationalInsuranceNumber->value => false,
            ]
        );

        $postOfficeOnly = array_merge($allRoutesUnavailable, [IdRoute::POST_OFFICE->value => true,]);

        $donor = ["personType" => PersonType::Donor->value];
        $certificateProvider = ["personType" => PersonType::CertificateProvider->value];
        $voucher = ["personType" => PersonType::Voucher->value];

        $noDecision = [
            "caseProgress" => [
                "fraudScore" => [
                    "decision" => "NODECISION",
                    "score" => 0
                ]
            ]
        ];

        $kbvsPassed = [
            "caseProgress" => [
                "kbvs" => [
                    "result" => true
                ]
            ]
        ];

        $docCheckedFailed = [
            "caseProgress" => [
                "docCheck" => [
                    "idDocument" => DocumentType::DrivingLicence->value,
                    "state" => false
                ],
            ]
        ];

        $stopAbandonedKbvs = [
            "caseProgress" => [
                "docCheck" => [
                    "idDocument" => DocumentType::DrivingLicence->value,
                    "state" => true
                ],
                "fraudScore" => [
                    "decision" => "STOP",
                    "score" => 999
                ]
            ]
        ];

        $continueAbandonedKbvs = [
            "caseProgress" => [
                "docCheck" => [
                    "idDocument" => DocumentType::DrivingLicence->value,
                    "state" => true
                ],
                "fraudScore" => [
                    "decision" => "CONTINUE",
                    "score" => 200,
                ]
            ]
        ];

        $stopFailedKbvs = [
            "caseProgress" => [
                "docCheck" => [
                    "idDocument" => DocumentType::DrivingLicence->value,
                    "state" => true
                ],
                "fraudScore" => [
                    "decision" => "STOP",
                    "score" => 999
                ],
                "kbvs" => [
                    "result" => false
                ]
            ]
        ];

        $continueFailedKbvs = [
            "caseProgress" => [
                "docCheck" => [
                    "idDocument" => DocumentType::DrivingLicence->value,
                    "state" => true
                ],
                "fraudScore" => [
                    "decision" => "CONTINUE",
                    "score" => 200,
                ],
                "kbvs" => [
                    "result" => false
                ]
            ]
        ];

        $thinfile = [
            "identityIQ" => [
                "kbvQuestions" => [],
                "iiqControl" => [
                    "urn" => "******",
                    "authRefNo" => "********"
                ],
                "thinfile" => true
            ]
        ];

        $ninoRestricted = [
            "caseProgress" => [
                "restrictedMethods" => [DocumentType::NationalInsuranceNumber->value]
            ]
        ];

        return [
            "fresh donor case - all routes available" => [
                $config,
                $donor,
                $externalServices,
                [
                    'data' => $donorAllRoutesAvailable,
                    'messages' => [],
                ],
            ],
            "fresh certificate-provider case - all but vouching available and CoP" => [
                $config,
                $certificateProvider,
                $externalServices,
                [
                    'data' => $otherAllRoutesAvailable,
                    'messages' => [],
                ],
            ],
            "fresh voucher case - all but vouching available and CoP" => [
                $config,
                $voucher,
                $externalServices,
                [
                    'data' => $otherAllRoutesAvailable,
                    'messages' => [],
                ],
            ],
            "already passed the ID check" => [
                $config,
                array_merge($donor, $kbvsPassed),
                $externalServices,
                [
                    'data' => $allRoutesUnavailable,
                    'messages' => ['The identity check has already been completed']
                ]
            ],
            "donor failed a doc-check" => [
                $config,
                array_merge($donor, $docCheckedFailed),
                $externalServices,
                [
                    'data' => $donorOffline,
                    'messages' => ['The donor failed doc-check']
                ]
            ],
            "certificate-provider failed a doc-check" => [
                $config,
                array_merge($certificateProvider, $docCheckedFailed),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The certificate provider failed doc-check']
                ]
            ],
            "voucher failed a doc-check" => [
                $config,
                array_merge($voucher, $docCheckedFailed),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The person vouching failed doc-check']
                ]
            ],
            "donor doc has already been checked abandoned kbvs and STOP fraudscore - block experian and vouching" => [
                $config,
                array_merge($donor, $stopAbandonedKbvs),
                $externalServices,
                [
                    'data' => array_merge(
                        $allRoutesUnavailable,
                        [
                            IdRoute::POST_OFFICE->value => true,
                            IdRoute::COURT_OF_PROTECTION->value => true,
                        ]
                    ),
                    'messages' => ['donor-vouch-unavailable']
                ]
            ],
            "certificate-provider doc has already been checked abandoned kbvs - close off experian routes" => [
                $config,
                array_merge($certificateProvider, $continueAbandonedKbvs),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The certificate provider has thinfile or failed KBVs']
                ]
            ],
            "voucher doc has already been checked abandoned kbvs - close off experian routes" => [
                $config,
                array_merge($voucher, $continueAbandonedKbvs),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The person vouching has thinfile or failed KBVs']
                ]
            ],
            "donor with NODECISION fraudscore - close off experian routes" => [
                $config,
                array_merge($donor, $noDecision),
                $externalServices,
                [
                    'data' => $donorOffline,
                    'messages' => ['The donor has thinfile or failed KBVs']
                ]
            ],
            "certificate-provider with NODECISION fraudscore - close off experian routes" => [
                $config,
                array_merge($certificateProvider, $noDecision),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The certificate provider has thinfile or failed KBVs']
                ]
            ],
            "voucher with NODECISION fraudscore - close off experian routes" => [
                $config,
                array_merge($voucher, $noDecision),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The person vouching has thinfile or failed KBVs']
                ]
            ],
            "donor with a thinfile (empty KBVs) - offline only" => [
                $config,
                array_merge($donor, $thinfile),
                $externalServices,
                [
                    'data' => $donorOffline,
                    'messages' => ['The donor has thinfile or failed KBVs']
                ]
            ],
            "certificate-provider with a thinfile (empty KBVs) - offline only" => [
                $config,
                array_merge($certificateProvider, $thinfile),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The certificate provider has thinfile or failed KBVs']
                ]
            ],
            "voucher with a thinfile (empty KBVs) - offline only" => [
                $config,
                array_merge($voucher, $thinfile),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The person vouching has thinfile or failed KBVs']
                ]
            ],
            "donor with STOP fraudscore and failed KBVs - only post-office and CoP available" => [
                $config,
                array_merge($donor, $stopFailedKbvs),
                $externalServices,
                [
                    'data' => array_merge(
                        $allRoutesUnavailable,
                        [
                            IdRoute::POST_OFFICE->value => true,
                            IdRoute::COURT_OF_PROTECTION->value => true,
                        ]
                    ),
                    'messages' => ['donor-vouch-unavailable'],
                ]
            ],
            "donor with CONTINUE fraudscore and failed KBVs - post-office, vouching and CoP available" => [
                $config,
                array_merge($donor, $continueFailedKbvs),
                $externalServices,
                [
                    'data' => $donorOffline,
                    'messages' => ['The donor has thinfile or failed KBVs'],
                ]
            ],
            "certificate-provider with failed KBVs - only post-office" => [
                $config,
                array_merge($certificateProvider, $stopFailedKbvs),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The certificate provider has thinfile or failed KBVs'],
                ]
            ],
            "voucher with failed KBVs - only post-office" => [
                $config,
                array_merge($voucher, $stopFailedKbvs),
                $externalServices,
                [
                    'data' => $postOfficeOnly,
                    'messages' => ['The person vouching has thinfile or failed KBVs'],
                ]
            ],
            "post-office route is unavailable" => [
                $config,
                $donor,
                $postOfficeUnavailable,
                [
                    'data' => array_merge(
                        $donorAllRoutesAvailable,
                        [IdRoute::POST_OFFICE->value => false]
                    ),
                    'messages' => []
                ]
            ],
            "experian route is unavailable" => [
                $config,
                $donor,
                $experianUnavailable,
                [
                    'data' => $donorOffline,
                    'messages' => ['Online identity verification is not presently available']
                ],
            ],
            "national-insurance-number route is unavailable" => [
                $config,
                $donor,
                $ninoUnavailable,
                [
                    'data' => array_merge(
                        $donorAllRoutesAvailable,
                        [DocumentType::NationalInsuranceNumber->value => false]
                    ),
                    'messages' => ['Some identity verification methods are not presently available']
                ]
            ],
            "passport route is unavailable" => [
                $config,
                $donor,
                $passportUnavailable,
                [
                    'data' => array_merge(
                        $donorAllRoutesAvailable,
                        [DocumentType::Passport->value => false]
                    ),
                    'messages' => ['Some identity verification methods are not presently available']
                ]
            ],
            "driving-licence route is unavailable" => [
                $config,
                $donor,
                $drivingLicenceUnavailable,
                [
                    'data' => array_merge(
                        $donorAllRoutesAvailable,
                        [DocumentType::DrivingLicence->value => false]
                    ),
                    'messages' => ['Some identity verification methods are not presently available']
                ]
            ],
            "nino, pp and dk routes all unavailable" => [
                $config,
                $donor,
                $allDocsUnavailable,
                [
                    'data' => array_merge(
                        $donorAllRoutesAvailable,
                        [
                            DocumentType::NationalInsuranceNumber->value => false,
                            DocumentType::Passport->value => false,
                            DocumentType::DrivingLicence->value => false,]
                    ),
                    'messages' => ['Some identity verification methods are not presently available']
                ]
            ],
            "donor had NINO restricted" => [
                $config,
                array_merge($donor, $ninoRestricted),
                $externalServices,
                [
                    'data' => array_merge(
                        $donorAllRoutesAvailable,
                        [DocumentType::NationalInsuranceNumber->value => false]
                    ),
                    'messages' => ['National Insurance number could not be verified over the phone...']
                ]
            ],
            "donor has fraudscore STOP, has failed KBVs and post-office is not available" => [
                $config,
                array_merge($donor, $stopFailedKbvs),
                $postOfficeUnavailable,
                [
                    'data' => array_merge($allRoutesUnavailable, [IdRoute::COURT_OF_PROTECTION->value => true]),
                    'messages' => ['donor-vouch-unavailable']
                ]
            ],
            "donor has NINO restricted and passport-service is not available" => [
                $config,
                array_merge($donor, $ninoRestricted),
                $passportUnavailable,
                [
                    'data' => array_merge(
                        $donorAllRoutesAvailable,
                        [
                            DocumentType::NationalInsuranceNumber->value => false,
                            DocumentType::Passport->value => false
                        ]
                    ),
                    // being review as part of ID-580
                    'messages' => [
                        'Some identity verification methods are not presently available',
                        'National Insurance number could not be verified over the phone...'
                    ]
                ],
            ],
        ];
    }
}
