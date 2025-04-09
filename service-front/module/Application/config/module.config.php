<?php

declare(strict_types=1);

namespace Application;

use Application\Auth\JwtGenerator;
use Application\Auth\JwtGeneratorFactory;
use Application\Auth\Listener as AuthListener;
use Application\Auth\ListenerFactory as AuthListenerFactory;
use Application\Controller\Factory\CourtOfProtectionFlowControllerFactory;
use Application\Controller\Factory\CPFlowControllerFactory;
use Application\Controller\Factory\DocumentCheckControllerFactory;
use Application\Controller\Factory\DonorFlowControllerFactory;
use Application\Controller\Factory\HowConfirmControllerFactory;
use Application\Controller\Factory\IndexControllerFactory;
use Application\Controller\Factory\PostOfficeFlowControllerFactory;
use Application\Controller\Factory\VouchingFlowControllerFactory;
use Application\Enums\IdRoute;
use Application\Enums\DocumentType;
use Application\Factories\LoggerFactory;
use Application\Factories\OpgApiServiceFactory;
use Application\Factories\SiriusApiServiceFactory;
use Application\PostOffice\DocumentTypeRepository;
use Application\PostOffice\DocumentTypeRepositoryFactory;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Application\Views\TwigExtension;
use Application\Views\TwigExtensionFactory;
use Exception;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Lcobucci\Clock\SystemClock;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Twig\Extension\DebugExtension;

$prefix = getenv("PREFIX");
if (! is_string($prefix)) {
    $prefix = '';
}

$yotiSupportedDocs = file_get_contents(__DIR__ . '/yoti-supported-documents.json');

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
                    'health_check' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/health-check',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'healthCheck',
                            ],
                        ],
                    ],
                    'health_check_service' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/health-check/service',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'healthCheckService',
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
                    'how_will_you_confirm' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/how-will-you-confirm',
                            'defaults' => [
                                'controller' => Controller\HowConfirmController::class,
                                'action' => 'howWillYouConfirm',
                            ],
                        ],
                    ],
                    'donor_lpa_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/donor-lpa-check',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'donorLpaCheck',
                            ],
                        ],
                    ],
                    'donor_details_match_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/donor-details-match-check',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'donorDetailsMatchCheck',
                            ],
                        ],
                    ],
                    'national_insurance_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/national-insurance-number',
                            'defaults' => [
                                'controller' => Controller\DocumentCheckController::class,
                                'action' => 'nationalInsuranceNumber',
                            ],
                        ],
                    ],
                    'driving_licence_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/driving-licence-number',
                            'defaults' => [
                                'controller' => Controller\DocumentCheckController::class,
                                'action' => 'drivingLicenceNumber',
                            ],
                        ],
                    ],
                    'passport_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/passport-number',
                            'defaults' => [
                                'controller' => Controller\DocumentCheckController::class,
                                'action' => 'passportNumber',
                            ],
                        ],
                    ],
                    'id_verify_questions' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/id-verify-questions',
                            'defaults' => [
                                'controller' => Controller\KbvController::class,
                                'action' => 'idVerifyQuestions',
                            ],
                        ],
                    ],
                    'identity_check_passed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/identity-check-passed',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'identityCheckPassed',
                            ],
                        ],
                    ],
                    'identity_check_failed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/identity-check-failed',
                            'defaults' => [
                                'controller' => Controller\KbvController::class,
                                'action' => 'identityCheckFailed',
                            ],
                        ],
                    ],
                    'thin_file_failure' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/thin-file-failure',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'thinFileFailure',
                            ],
                        ],
                    ],
                    'proving_identity' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/proving-identity',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'provingIdentity',
                            ],
                        ],
                    ],
                    'post_office_documents' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/post-office-documents',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'postOfficeDocuments',
                            ],
                        ],
                    ],
                    'court_of_protection' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/court-of-protection',
                            'defaults' => [
                                'controller' => Controller\CourtOfProtectionFlowController::class,
                                'action' => 'register',
                            ],
                        ],
                    ],
                    'court_of_protection_what_next' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/court-of-protection-what-next',
                            'defaults' => [
                                'controller' => Controller\CourtOfProtectionFlowController::class,
                                'action' => 'whatNext',
                            ],
                        ],
                    ],
                    'find_post_office_branch' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/find-post-office-branch',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'findPostOfficeBranch',
                            ],
                        ],
                    ],
                    'po_what_happens_next' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/post-office-what-happens-next',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'whatHappensNext',
                            ],
                        ],
                    ],
                    'post_office_route_not_available' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/post-office-route-not-available',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'postOfficeRouteNotAvailable',
                            ],
                        ],
                    ],
                    'cp_name_match_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/name-match-check',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'nameMatchCheck',
                            ],
                        ],
                    ],
                    'cp_confirm_lpas' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/confirm-lpas',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'confirmLpas',
                            ],
                        ],
                    ],
                    'cp_add_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/add-lpa',
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
                    'cp_identity_check_passed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/identity-check-passed',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'identityCheckPassed',
                            ],
                        ],
                    ],
                    'cp_enter_postcode' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/enter-postcode',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'enterPostcode',
                            ],
                        ],
                    ],
                    'cp_enter_address_manual' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/enter-address-manual',
                            'defaults' => [
                                'controller' => Controller\CPFlowController::class,
                                'action' => 'enterAddressManual',
                            ],
                        ],
                    ],
                    'cp_select_address' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/cp/select-address/:postcode',
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
                    'po_choose_country' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/po-choose-country',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'chooseCountry',
                            ],
                        ],
                    ],
                    'po_choose_country_id' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/po-choose-country-id',
                            'defaults' => [
                                'controller' => Controller\PostOfficeFlowController::class,
                                'action' => 'chooseCountryId',
                            ],
                        ],
                    ],
                    'what_is_vouching' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/what-is-vouching',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'whatIsVouching',
                            ],
                        ],
                    ],
                    'vouching_what_happens_next' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching-what-happens-next',
                            'defaults' => [
                                'controller' => Controller\DonorFlowController::class,
                                'action' => 'vouchingWhatHappensNext',
                            ],
                        ],
                    ],
                    'confirm_vouching' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/confirm-vouching',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'confirmVouching',
                            ],
                        ],
                    ],
                    'voucher_name' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/voucher-name',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'voucherName',
                            ],
                        ],
                    ],
                    'voucher_dob' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/voucher-dob',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'voucherDob',
                            ],
                        ],
                    ],
                    'voucher_enter_postcode' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/enter-postcode',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'enterPostcode',
                            ],
                        ],
                    ],
                    'voucher_select_address' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/select-address/:postcode',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'selectAddress',
                            ],
                        ],
                    ],
                    'voucher_enter_address_manual' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/enter-address-manual',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'enterAddressManual',
                            ],
                        ],
                    ],
                    'voucher_confirm_donors' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/confirm-donors',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'confirmDonors',
                            ],
                        ],
                    ],
                    'voucher_add_donor' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/add-donor',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'addDonor',
                            ],
                        ],
                    ],
                    'voucher_remove_lpa' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/remove-lpa/:lpa',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'removeLpa',
                            ],
                        ],
                    ],
                    'voucher_identity_check_passed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching/identity-check-passed',
                            'defaults' => [
                                'controller' => Controller\VouchingFlowController::class,
                                'action' => 'identityCheckPassed',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\CPFlowController::class => CPFlowControllerFactory::class,
            Controller\DonorFlowController::class => DonorFlowControllerFactory::class,
            Controller\DocumentCheckController::class => DocumentCheckControllerFactory::class,
            Controller\HowConfirmController::class => HowConfirmControllerFactory::class,
            Controller\VouchingFlowController::class => VouchingFlowControllerFactory::class,
            Controller\IndexController::class => IndexControllerFactory::class,
            Controller\KbvController::class => LazyControllerAbstractFactory::class,
            Controller\PostOfficeFlowController::class => PostOfficeFlowControllerFactory::class,
            Controller\CourtOfProtectionFlowController::class => CourtOfProtectionFlowControllerFactory::class,
        ],
    ],
    'listeners' => [
        AuthListener::class,
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
            ClockInterface::class => fn () => SystemClock::fromSystemTimezone(),
            JwtGenerator::class => JwtGeneratorFactory::class,
            LoggerInterface::class => LoggerFactory::class,
            OpgApiService::class => OpgApiServiceFactory::class,
            SiriusApiService::class => SiriusApiServiceFactory::class,
            TwigExtension::class => TwigExtensionFactory::class,
            DocumentTypeRepository::class => DocumentTypeRepositoryFactory::class,
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
        'identity_documents' => [
            DocumentType::NationalInsuranceNumber->value => 'National Insurance number',
            DocumentType::Passport->value => "UK Passport (current or expired in the last 18 months)",
            DocumentType::DrivingLicence->value => 'UK driving licence (must be current)',
        ],
        'identity_routes' => [
            IdRoute::POST_OFFICE->value => 'Post Office',
            IdRoute::VOUCHING->value => 'Have someone vouch for the identity of the donor',
            IdRoute::COURT_OF_PROTECTION->value => 'Court of protection',
        ],
        'template_options' => [
            DocumentType::NationalInsuranceNumber->value => [
                'default' => 'application/pages/national_insurance_number',
                'success' => 'application/pages/document_success',
                'fail' => 'application/pages/national_insurance_number_fail',
                'amb_fail' => 'application/pages/national_insurance_number_ambiguous_fail',
                'thin_file' => 'application/pages/thin_file_failure',
                'fraud' => 'application/pages/fraud_failure',
            ],
            DocumentType::Passport->value => [
                'default' => 'application/pages/passport_number',
                'success' => 'application/pages/document_success',
                'fail' => 'application/pages/passport_number_fail',
                'thin_file' => 'application/pages/thin_file_failure',
                'fraud' => 'application/pages/fraud_failure',
            ],
            DocumentType::DrivingLicence->value => [
                'default' => 'application/pages/driving_licence_number',
                'success' => 'application/pages/document_success',
                'fail' => 'application/pages/driving_licence_number_fail',
                'thin_file' => 'application/pages/thin_file_failure',
                'fraud' => 'application/pages/fraud_failure',
            ],
        ],
        'yoti_supported_documents' => json_decode(
            $yotiSupportedDocs === false ? '' : $yotiSupportedDocs,
            true
        ),
    ],
];
