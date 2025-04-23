<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Helpers;

use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Helpers\ServiceAvailabilityHelper;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\TestCase;

class ServiceAvailabilityHelperTest extends TestCase
{
    /**
     * @dataProvider data
     */
    public function testProcessCase(
        array $config,
        array $caseData,
        array $externalServices,
        array $expected
    ): void {
        $case = CaseData::fromArray($caseData);
        $helper = new ServiceAvailabilityHelper($externalServices, $config);
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
                    'NODECISION' => 'no-decision-message',
                    'DONOR_STOP' => 'donor-stop-message',
                    'DONOR_STOP_VOUCH_AVAILABLE' => 'donor-stop-vouching-available-message',
                    'CP_STOP' => 'cp-stop-message',
                    'VOUCHER_STOP' => 'voucher-stop-message',
                    'LOCKED_ID_SUCCESS' => 'The %s has already passed doc-check',
                    'LOCKED' => 'The %s failed doc-check',
                    'LOCKED_SUCCESS' => 'The identity check has already been completed',
                    'RESTRICTED_OPTIONS' => '%s could not be verified over the phone...'
                ],
                'person_type_labels' => [
                    'donor' => 'donor',
                    'certificateProvider' => 'certificate provider',
                    'voucher' => 'person vouching'
                ]
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

        $allRoutesAvailable = [
            IdRoute::KBV->value => true,
            DocumentType::Passport->value => true,
            DocumentType::DrivingLicence->value => true,
            DocumentType::NationalInsuranceNumber->value => true,
            IdRoute::POST_OFFICE->value => true,
            IdRoute::VOUCHING->value => false,  // set to false as only available for donor so easier to add back in.
            IdRoute::COURT_OF_PROTECTION->value => true,
        ];

        $vouchingAvailable = [IdRoute::VOUCHING->value => true];

        $allRoutesUnavailable = [
            IdRoute::KBV->value => false,
            DocumentType::Passport->value => false,
            DocumentType::DrivingLicence->value => false,
            DocumentType::NationalInsuranceNumber->value => false,
            IdRoute::POST_OFFICE->value => false,
            IdRoute::VOUCHING->value => false,
            IdRoute::COURT_OF_PROTECTION->value => false,
        ];

        $offlineRoutesOnly = array_merge(
            $allRoutesUnavailable,
            [
                IdRoute::POST_OFFICE->value => true,
                IdRoute::COURT_OF_PROTECTION->value => true,
            ]
        );


        $donor = ["personType" => "donor"];
        $certificateProvider = ["personType" => "certificateProvider"];
        $voucher = ["personType" => "voucher"];

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

        $docCheckedPassed = [
            "caseProgress" => [
                "docCheck" => [
                    "idDocument" => DocumentType::DrivingLicence->value,
                    "state" => true
                ],
            ]
        ];

        $stopFailedKbvs = [
            "caseProgress" => [
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
                    'data' => array_merge($allRoutesAvailable, $vouchingAvailable),
                    'messages' => [],
                ],
            ],
            "fresh certificate-provider case - all but vouching available" => [
                $config,
                $certificateProvider,
                $externalServices,
                [
                    'data' => $allRoutesAvailable,
                    'messages' => [],
                ],
            ],
            "fresh voucher case - all but vouching available" => [
                $config,
                $voucher,
                $externalServices,
                [
                    'data' => $allRoutesAvailable,
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
                    'data' => array_merge($offlineRoutesOnly, $vouchingAvailable),
                    'messages' => ['The donor failed doc-check']
                ]
            ],
            "certificate-provider failed a doc-check" => [
                $config,
                array_merge($certificateProvider, $docCheckedFailed),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['The certificate provider failed doc-check']
                ]
            ],
            "voucher failed a doc-check" => [
                $config,
                array_merge($voucher, $docCheckedFailed),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['The person vouching failed doc-check']
                ]
            ],
            // TODO: is this actually the correct behaviour??
            "donor doc has already been checked - close off experian routes" => [
                $config,
                array_merge($donor, $docCheckedPassed),
                $externalServices,
                [
                    'data' => array_merge($offlineRoutesOnly, $vouchingAvailable),
                    'messages' => ['The donor has already passed doc-check']
                ]
            ],
            "certificate-provider doc has already been checked - close off experian routes" => [
                $config,
                array_merge($certificateProvider, $docCheckedPassed),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['The certificate provider has already passed doc-check']
                ]
            ],
            "voucher doc has already been checked - close off experian routes" => [
                $config,
                array_merge($voucher, $docCheckedPassed),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['The person vouching has already passed doc-check']
                ]
            ],
            "donor with NODECISION fraudscore - ???" => [
                $config,
                array_merge($certificateProvider, $noDecision),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['no-decision-message']
                ]
            ],
            "certificate-provider with NODECISION fraudscore - ???" => [
                $config,
                array_merge($certificateProvider, $noDecision),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['no-decision-message']  // TODO: deffo not right as mentions 'donor'
                ]
            ],
            "voucher with NODECISION fraudscore - ???" => [
                $config,
                array_merge($voucher, $noDecision),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['no-decision-message']  // TODO: deffo not right as mentions 'donor'
                ]
            ],
            "donor with a thinfile (empty KBVs) - offline only" => [
                $config,
                array_merge($donor, $thinfile),
                $externalServices,
                [
                    'data' => array_merge($offlineRoutesOnly, $vouchingAvailable),
                    'messages' => ['no-decision-message']
                ]
            ],
            "certificate-provider with a thinfile (empty KBVs) - offline only" => [
                $config,
                array_merge($certificateProvider, $thinfile),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['no-decision-message']  // TODO: deffo not right as mentions 'donor'
                ]
            ],
            "voucher with a thinfile (empty KBVs) - offline only" => [
                $config,
                array_merge($voucher, $thinfile),
                $externalServices,
                [
                    'data' => $offlineRoutesOnly,
                    'messages' => ['no-decision-message']  // TODO: deffo not right as mentions 'donor'
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
                    'messages' => ['donor-stop-message'],
                ]
            ],
            "donor with CONTINUE fraudscore and failed KBVs - post-office, vouching and CoP available" => [
                $config,
                array_merge($donor, $continueFailedKbvs),
                $externalServices,
                [
                    'data' => array_merge(
                        $allRoutesUnavailable,
                        [
                            IdRoute::POST_OFFICE->value => true,
                            IdRoute::VOUCHING->value => true,
                            IdRoute::COURT_OF_PROTECTION->value => true,
                        ]
                    ),
                    'messages' => ['donor-stop-vouching-available-message'],
                ]
            ],
            "certificate-provider with failed KBVs - only post-office and CoP available" => [
                $config,
                array_merge($certificateProvider, $stopFailedKbvs),
                $externalServices,
                [
                    'data' => array_merge(
                        $allRoutesUnavailable,
                        [
                            IdRoute::POST_OFFICE->value => true,
                            IdRoute::COURT_OF_PROTECTION->value => true,
                        ]
                    ),
                    'messages' => ['cp-stop-message'],
                ]
            ],
            "voucher with failed KBVs - only post-office and CoP available" => [
                $config,
                array_merge($voucher, $stopFailedKbvs),
                $externalServices,
                [
                    'data' => array_merge(
                        $allRoutesUnavailable,
                        [
                            IdRoute::POST_OFFICE->value => true,
                            IdRoute::COURT_OF_PROTECTION->value => true,
                        ]
                    ),
                    'messages' => ['voucher-stop-message'],
                ]
            ],
            "post-office route is unavailable" => [
                $config,
                $donor,
                $postOfficeUnavailable,
                [
                    'data' => array_merge(
                        $allRoutesAvailable,
                        $vouchingAvailable,
                        [IdRoute::POST_OFFICE->value => false]
                    ),
                    'messages' => []  //TODO: is it not a bit strange that their isn't a message here???
                ]
            ],
            "experian route is unavailable" => [
                $config,
                $donor,
                $experianUnavailable,
                [
                    'data' => array_merge($offlineRoutesOnly, $vouchingAvailable),
                    //TODO: is this the message we want?
                    'messages' => ['Online identity verification is not presently available']
                ],
            ],
            "national-insurance-number route is unavailable" => [
                $config,
                $donor,
                $ninoUnavailable,
                [
                    'data' => array_merge(
                        $allRoutesAvailable,
                        $vouchingAvailable,
                        [DocumentType::NationalInsuranceNumber->value => false]
                    ),
                    //TODO: is this the message we want?
                    'messages' => ['Some identity verification methods are not presently available']
                ]
            ],
            "passport route is unavailable" => [
                $config,
                $donor,
                $passportUnavailable,
                [
                    'data' => array_merge(
                        $allRoutesAvailable,
                        $vouchingAvailable,
                        [DocumentType::Passport->value => false]
                    ),
                    //TODO: is this the message we want?
                    'messages' => ['Some identity verification methods are not presently available']
                ]
            ],
            "driving-licence route is unavailable" => [
                $config,
                $donor,
                $drivingLicenceUnavailable,
                [
                    'data' => array_merge(
                        $allRoutesAvailable,
                        $vouchingAvailable,
                        [DocumentType::DrivingLicence->value => false]
                    ),
                    //TODO: is this the message we want?
                    'messages' => ['Some identity verification methods are not presently available']
                ]
            ],
            "nino, pp and dk routes all unavailable" => [
                $config,
                $donor,
                $allDocsUnavailable,
                [
                    'data' => array_merge(
                        $allRoutesAvailable,
                        $vouchingAvailable,
                        [
                            DocumentType::NationalInsuranceNumber->value => false,
                            DocumentType::Passport->value => false,
                            DocumentType::DrivingLicence->value => false,]
                    ),
                    //TODO: is this the message we want?
                    'messages' => ['Some identity verification methods are not presently available']
                ]
            ],
            "donor had NINO restricted" => [
                $config,
                array_merge($donor, $ninoRestricted),
                $externalServices,
                [
                    'data' => array_merge(
                        $allRoutesAvailable,
                        $vouchingAvailable,
                        [DocumentType::NationalInsuranceNumber->value => false]
                    ),
                    //TODO: is this the message we want?
                    'messages' => ['National Insurance number could not be verified over the phone...']
                ]
            ],
            "donor has fraudscore STOP, has failed KBVs and post-office is not available" => [
                $config,
                array_merge($donor, $stopFailedKbvs),
                $postOfficeUnavailable,
                [
                    'data' => array_merge($allRoutesUnavailable, [IdRoute::COURT_OF_PROTECTION->value => true]),
                    'messages' => ['donor-stop-message']
                ]
            ],
            //TODO: is this the behaviour we want?
            "donor has NINO restricted and passport-service is not available" => [
                $config,
                array_merge($donor, $ninoRestricted),
                $passportUnavailable,
                [
                    'data' => array_merge(
                        $allRoutesAvailable,
                        $vouchingAvailable,
                        [
                            DocumentType::NationalInsuranceNumber->value => false,
                            DocumentType::Passport->value => false
                        ]
                    ),
                    'messages' => [
                        'Some identity verification methods are not presently available',
                        'National Insurance number could not be verified over the phone...'
                    ]
                ],
            ],
        ];
    }
}
