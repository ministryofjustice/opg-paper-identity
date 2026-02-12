<?php

declare(strict_types=1);

namespace Application;

use Application\Auth\JwtGenerator;
use Application\Auth\JwtGeneratorFactory;
use Application\Auth\Listener as AuthListener;
use Application\Auth\ListenerFactory as AuthListenerFactory;
use Application\Controller\Factory\CPFlowControllerFactory;
use Application\Controller\Factory\VouchingFlowControllerFactory;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Factories\LoggerFactory;
use Application\Factories\OpgApiServiceFactory;
use Application\Factories\SiriusApiServiceFactory;
use Application\Handler\PostOffice\FindPostOfficeBranchHandlerFactory;
use Application\Helpers\RouteHelper;
use Application\Helpers\RouteHelperFactory;
use Application\PostOffice\DocumentTypeRepository;
use Application\PostOffice\DocumentTypeRepositoryFactory;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Application\Views\TwigExtension;
use Application\Views\TwigExtensionFactory;
use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Lcobucci\Clock\SystemClock;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigRenderer;
use Mezzio\Twig\TwigRendererFactory;
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
                    'health_check' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/health-check',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Handler\HealthCheck\StatusHandler::class
                                ),
                            ],
                        ],
                    ],
                    'health_check_service' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/health-check/service',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Handler\HealthCheck\ServiceStatusHandler::class
                                ),
                            ],
                        ],
                    ],
                    'start' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/start',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => Handler\StartHandler::class,
                            ],
                        ],
                    ],
                    'how_will_you_confirm' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/how-will-you-confirm',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\HowConfirm\HowWillYouConfirmHandler::class
                                ),
                            ],
                        ],
                    ],
                    'donor_lpa_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/donor-lpa-check',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Donor\LpaCheckHandler::class
                                ),
                            ],
                        ],
                    ],
                    'donor_details_match_check' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/donor-details-match-check',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Donor\DonorDetailsMatchCheckHandler::class
                                ),
                            ],
                        ],
                    ],
                    'national_insurance_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/national-insurance-number',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\DocumentCheck\NationalInsuranceNumberHandler::class
                                ),
                            ],
                        ],
                    ],
                    'driving_licence_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/driving-licence-number',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\DocumentCheck\DrivingLicenceNumberHandler::class
                                ),
                            ],
                        ],
                    ],
                    'passport_number' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/passport-number',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\DocumentCheck\PassportNumberHandler::class
                                ),
                            ],
                        ],
                    ],
                    'id_verify_questions' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/id-verify-questions',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Kbv\QuestionsHandler::class
                                ),
                            ],
                        ],
                    ],
                    'identity_check_passed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/identity-check-passed',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Donor\IdentityCheckPassedHandler::class
                                ),
                            ],
                        ],
                    ],
                    'identity_check_failed' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/identity-check-failed',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Kbv\IdentityCheckFailedHandler::class
                                ),
                            ],
                        ],
                    ],
                    'thin_file_failure' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/thin-file-failure',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Donor\ThinFileFailureHandler::class
                                ),
                            ],
                        ],
                    ],
                    'post_office_documents' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/post-office-documents',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\PostOffice\ChooseUKDocumentHandler::class
                                ),
                            ],
                        ],
                    ],
                    'court_of_protection' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/court-of-protection',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\CourtOfProtection\RegisterHandler::class
                                ),
                            ],
                        ],
                    ],
                    'court_of_protection_what_next' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/court-of-protection-what-next',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\CourtOfProtection\WhatNextHandler::class
                                ),
                            ],
                        ],
                    ],
                    'find_post_office_branch' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/find-post-office-branch',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\PostOffice\FindPostOfficeBranchHandler::class
                                ),
                            ],
                        ],
                    ],
                    'po_what_happens_next' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/post-office-what-happens-next',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\PostOffice\WhatHappensNextHandler::class
                                ),
                            ],
                        ],
                    ],
                    'post_office_route_not_available' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/post-office-route-not-available',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\PostOffice\RouteNotAvailableHandler::class
                                ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Donor\RemoveLpaHandler::class
                                ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\AbandonFlowHandler::class
                                ),
                            ],
                        ],
                    ],
                    'po_choose_country' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/po-choose-country',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\PostOffice\ChooseCountryHandler::class
                                ),
                            ],
                        ],
                    ],
                    'po_choose_country_id' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/po-choose-country-id',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\PostOffice\ChooseInternationalDocumentHandler::class
                                ),
                            ],
                        ],
                    ],
                    'what_is_vouching' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/what-is-vouching',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Donor\WhatIsVouchingHandler::class
                                ),
                            ],
                        ],
                    ],
                    'vouching_what_happens_next' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:uuid/vouching-what-happens-next',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    Middleware\AttributePromotionMiddleware::class,
                                    Handler\Donor\VouchingWhatHappensNextHandler::class
                                ),
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
            Controller\VouchingFlowController::class => VouchingFlowControllerFactory::class,
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
        'default_template_suffix' => 'twig',
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'templates' => [
        'extension' => 'twig',
        'layout' => 'layout/plain',
        'map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
        ],
    ],
    'service_manager' => [
        'aliases' => [
            Contracts\OpgApiServiceInterface::class => Services\OpgApiService::class,
            TemplateRendererInterface::class => TwigRenderer::class,
        ],
        'factories' => [
            AuthListener::class => AuthListenerFactory::class,
            ClockInterface::class => fn () => SystemClock::fromSystemTimezone(),
            JwtGenerator::class => JwtGeneratorFactory::class,
            LoggerInterface::class => LoggerFactory::class,
            OpgApiService::class => OpgApiServiceFactory::class,
            RouteHelper::class => RouteHelperFactory::class,
            SiriusApiService::class => SiriusApiServiceFactory::class,
            TwigExtension::class => TwigExtensionFactory::class,
            DocumentTypeRepository::class => DocumentTypeRepositoryFactory::class,
            TwigRenderer::class => TwigRendererFactory::class,
            // Handlers
            Handler\PostOffice\FindPostOfficeBranchHandler::class => FindPostOfficeBranchHandlerFactory::class,
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
        'yoti_supported_documents' => json_decode(
            $yotiSupportedDocs === false ? '' : $yotiSupportedDocs,
            true
        ),
    ],
];
