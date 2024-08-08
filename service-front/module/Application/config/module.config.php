<?php

declare(strict_types=1);

namespace Application;

use Application\Auth\Listener as AuthListener;
use Application\Auth\ListenerFactory as AuthListenerFactory;
use Application\Factories\ConfigHelperFactory;
use Application\Factories\LocalisationHelperFactory;
use Application\Factories\LoggerFactory;
use Application\Factories\OpgApiServiceFactory;
use Application\Factories\SiriusApiServiceFactory;
use Application\Helpers\ConfigHelper;
use Application\Helpers\LocalisationHelper;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Application\Views\TwigExtension;
use Application\Views\TwigExtensionFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Psr\Log\LoggerInterface;
use Twig\Extension\DebugExtension;

$prefix = getenv("PREFIX");
if (! is_string($prefix)) {
    $prefix = '';
}

return [
    'router' => [
        'routes' => [
            'root' => [
                'type' => Segment::class,
                'options' => [
                    'route' => $prefix,
                ],
                'child_routes' => [
                    'home' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'start' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/start',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'start',
                            ],
                        ],
                    ],
                    'donor_id_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/donor-id-check',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'donorIdCheck',
                            ],
                        ],
                    ],
                    'donor_lpa_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/donor-lpa-check',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'donorLpaCheck',
                            ],
                        ],
                    ],
                    'how_donor_confirms' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/how-will-donor-confirm',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'howWillDonorConfirm',
                            ],
                        ],
                    ],
                    'donor_details_match_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/donor-details-match-check',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'donorDetailsMatchCheck',
                            ],
                        ],
                    ],
                    'address_verification' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/address_verification',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'addressVerification',
                            ],
                        ],
                    ],
                    'national_insurance_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/national-insurance-number',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'nationalInsuranceNumber',
                            ],
                        ],
                    ],
                    'driving_licence_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/driving-licence-number',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'drivingLicenceNumber',
                            ],
                        ],
                    ],
                    'passport_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/passport-number',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'passportNumber',
                            ],
                        ],
                    ],
                    'id_verify_questions' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/id-verify-questions',
                            'defaults' => [
                                'controller' => Controller\KbvController::class,
                                'action' => 'idVerifyQuestions',
                            ],
                        ],
                    ],
                    'identity_check_passed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/identity-check-passed',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'identityCheckPassed',
                            ],
                        ],
                    ],
                    'identity_check_failed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/identity-check-failed',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'identityCheckFailed',
                            ],
                        ],
                    ],
                    'thin_file_failure' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/thin-file-failure',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'thinFileFailure',
                            ],
                        ],
                    ],
                    'proving_identity' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/proving-identity',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'provingIdentity',
                            ],
                        ],
                    ],
                    'post_office_documents' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/post-office-documents',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'postOfficeDocuments',
                            ],
                        ],
                    ],
                    'po_do_details_match' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/post-office-do-details-match',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'doDetailsMatch',
                            ],
                        ],
                    ],
                    'po_donor_lpa_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/post-office-donor-lpa-check',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'donorLpaCheck',
                            ],
                        ],
                    ],
                    'find_post_office_branch' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/find-post-office-branch',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'findPostOfficeBranch',
                            ],
                        ],
                    ],
                    'confirm_post_office' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/confirm-post-office',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'confirmPostOffice',
                            ],
                        ],
                    ],
                    'what_happens_next' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/what-happens-next',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'whatHappensNext',
                            ],
                        ],
                    ],
                    'post_office_route_not_available' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/post-office-route-not-available',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'postOfficeRouteNotAvailable',
                            ],
                        ],
                    ],
                    'cp_how_cp_confirms' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/how-will-cp-confirm',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'howWillCpConfirm',
                            ],
                        ],
                    ],
                    'cp_post_office_documents' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/post-office-documents',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'postOfficeDocuments',
                            ],
                        ],
                    ],
                    'cp_choose_country' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/choose-country',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'chooseCountry',
                            ],
                        ],
                    ],
                    'cp_choose_country_id' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/choose-country-id',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'chooseCountryId',
                            ],
                        ],
                    ],
                    'cp_name_match_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/name-match-check',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'nameMatchCheck',
                            ],
                        ],
                    ],
                    'cp_confirm_lpas' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/confirm-lpas',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'confirmLpas',
                            ],
                        ],
                    ],
                    'cp_add_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/add-lpa',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'addLpa',
                            ],
                        ],
                    ],
                    'cp_confirm_dob' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/confirm-dob',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'confirmDob',
                            ],
                        ],
                    ],
                    'cp_confirm_address' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/confirm-address',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'confirmAddress',
                            ],
                        ],
                    ],
                    'cp_national_insurance_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/national-insurance-number',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'nationalInsuranceNumber',
                            ],
                        ],
                    ],
                    'cp_driving_licence_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/driving-licence-number',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'drivingLicenceNumber',
                            ],
                        ],
                    ],
                    'cp_passport_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/passport-number',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'passportNumber',
                            ],
                        ],
                    ],
                    'cp_id_verify_questions' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/id-verify-questions',
                            'defaults' => [
                                'controller' => Controller\KbvController::class,
                                'action' => 'idVerifyQuestions',
                            ],
                        ],
                    ],
                    'cp_identity_check_passed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/identity-check-passed',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'identityCheckPassed',
                            ],
                        ],
                    ],
                    'cp_identity_check_failed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/identity-check-failed',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'identityCheckFailed',
                            ],
                        ],
                    ],
                    'cp_enter_postcode' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/enter-postcode',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'enterPostcode',
                            ],
                        ],
                    ],
                    'cp_enter_address_manual' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/enter-address-manual',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'enterAddressManual',
                            ],
                        ],
                    ],
                    'cp_select_address' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:uuid]/cp/select-address',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'selectAddress',
                            ],
                        ],
                    ],
                    'remove_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/remove-lpa/:lpa',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'removeLpa',
                            ],
                        ],
                    ],
                    'cp_remove_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/remove-lpa/:lpa',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'removeLpa',
                            ],
                        ],
                    ],
                    'po_remove_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/remove-lpa/:lpa',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'removeLpa',
                            ],
                        ],
                    ],
                    'abandon_flow' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/abandon-flow',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'abandonFlow',
                            ],
                        ],
                    ],
                    'donor_choose_country' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/donor-choose-country',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'chooseCountry',
                            ],
                        ],
                    ],
                    'donor_choose_country_id' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/donor-choose-country-id',
                            'defaults' => [
                                'controller' => Controller\DonorPostOfficeFlowController::class,
                                'action' => 'chooseCountryId',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\CPFlowController::class => LazyControllerAbstractFactory::class,
            Controller\DonorFlowController::class => LazyControllerAbstractFactory::class,
            Controller\IndexController::class => LazyControllerAbstractFactory::class,
            Controller\KbvController::class => LazyControllerAbstractFactory::class,
            Controller\DonorPostOfficeFlowController::class => LazyControllerAbstractFactory::class,
        ],
    ],
    'listeners' => [
        AuthListener::class
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'layout' => 'layout/plain',
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'service_manager' => [
        'aliases' => [
            Contracts\OpgApiServiceInterface::class => Services\OpgApiService::class,
        ],
        'factories' => [
            AuthListener::class => AuthListenerFactory::class,
            LoggerInterface::class => LoggerFactory::class,
            OpgApiService::class => OpgApiServiceFactory::class,
            SiriusApiService::class => SiriusApiServiceFactory::class,
            TwigExtension::class => TwigExtensionFactory::class,
            LocalisationHelper::class => LocalisationHelperFactory::class,
        ],
    ],
    'zend_twig' => [
        'extensions' => [
            TwigExtension::class,
            DebugExtension::class,
        ],
        'environment' => [
            'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
        ],
    ],
    'opg_settings' => [
        'identity_methods' => [
            'nin' => 'National Insurance number',
            'pn' => 'UK Passport (current or expired in the last 5 years)',
            'dln' => 'UK photocard driving licence (must be current) ',
        ],
        'post_office_identity_methods' => [
            'po_ukp' => 'UK passport (up to 18 months expired)',
            'po_eup' => 'EU passport (must be current)',
            'po_inp' => 'International passport (must be current)',
            'po_ukd' => 'UK Driving licence (must be current)',
            'po_eud' => 'EU Driving licence (must be current)',
            'po_ind' => 'International driving licence (must be current)',
            'po_n' => 'None of the above',
        ],
        'non_uk_identity_methods' => [
            'xpn' => 'Passport',
            'xdln' => 'Photocard driving licence',
            'xid' => 'National identity card',
        ],
        'acceptable_nations_for_id_documents' => [
            'AUT' => 'Austria',
            'BEL' => 'Belgium',
            'BGR' => 'Bulgaria',
            'HRV' => 'Croatia',
            'CYP' => 'Cyprus',
            'CZE' => 'Czechia',
            'DNK' => 'Denmark',
            'EST' => 'Estonia',
            'FIN' => 'Finland',
            'FRA' => 'France',
            'DEU' => 'Germany',
            'GRC' => 'Greece',
            'HUN' => 'Hungary',
            'IRL' => 'Ireland',
            'ITA' => 'Italy',
            'LVA' => 'Latvia',
            'LTU' => 'Lithuania',
            'LUX' => 'Luxembourg',
            'MLT' => 'Malta',
            'NLD' => 'Netherlands',
            'POL' => 'Poland',
            'PRT' => 'Portugal',
            'ROU' => 'Romania',
            'SKV' => 'Slovakia',
            'SVN' => 'Slovenia',
            'ESP' => 'Spain',
            'SWE' => 'Sweden',
        ],
        "supported_countries_documents" => [
            [
                "code" => "AUT",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2002-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "BEL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "BGR",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "CYP",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "CZE",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "DEU",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "DNK",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "ESP",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2006-06-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "EST",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "FIN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2011-05-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "FRA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-09-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "GRC",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "HRV",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2013-07-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "HUN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2020-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "IRL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2015-09-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "ITA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "1999-01-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2016-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "LTU",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "LUX",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "LVA",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "MLT",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "NLD",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2014-03-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "POL",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2001-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "PRT",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2007-01-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "ROU",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "SVN",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ],
            [
                "code" => "SWE",
                "supported_documents" => [
                    [
                        "type" => "DRIVING_LICENCE",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2007-12-01"
                        ]
                    ],
                    [
                        "type" => "NATIONAL_ID",
                        "is_strictly_latin" => true,
                        "requirements" => [
                            "date_from" => "2009-06-01"
                        ]
                    ],
                    [
                        "type" => "PASSPORT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "RESIDENCE_PERMIT",
                        "is_strictly_latin" => true
                    ],
                    [
                        "type" => "TRAVEL_DOCUMENT",
                        "is_strictly_latin" => true
                    ]
                ]
            ]
        ]
    ]
];
