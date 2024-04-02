<?php

declare(strict_types=1);

namespace Application;

use Application\Auth\Listener as AuthListener;
use Application\Auth\ListenerFactory as AuthListenerFactory;
use Application\Factories\LoggerFactory;
use Application\Factories\OpgApiServiceFactory;
use Application\Factories\SiriusApiServiceFactory;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Application\Views\TwigExtension;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Psr\Log\LoggerInterface;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'start' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/start',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'start',
                    ],
                ],
            ],
            'donor_id_check' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/donor-id-check',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'donorIdCheck',
                    ],
                ],
            ],
            'donor_lpa_check' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/donor-lpa-check',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'donorLpaCheck',
                    ],
                ],
            ],
            'how_donor_confirms' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/how-will-donor-confirm',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'howWillDonorConfirm',
                    ],
                ],
            ],
            'address_verification' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/address_verification',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'addressVerification',
                    ],
                ],
            ],
            'national_insurance_number' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/national-insurance-number',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'nationalInsuranceNumber',
                    ],
                ],
            ],
            'driving_licence_number' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/driving-licence-number',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'drivingLicenceNumber',
                    ],
                ],
            ],
            'passport_number' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/passport-number',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'passportNumber',
                    ],
                ],
            ],
            'id_verify_questions' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/id-verify-questions',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'idVerifyQuestions',
                    ],
                ],
            ],
            'identity_check_passed' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/identity-check-passed',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'identityCheckPassed',
                    ],
                ],
            ],
            'identity_check_failed' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/identity-check-failed',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'identityCheckFailed',
                    ],
                ],
            ],
            'thin_file_failure' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:uuid]/thin-file-failure',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'thinFileFailure',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => LazyControllerAbstractFactory::class,
        ],
    ],
    'listeners' => [
//        AuthListener::class
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'service_manager' => [
        'aliases' => [
            Contracts\OpgApiServiceInterface::class => Services\OpgApiService::class,
        ],
        'invokables' => [
            TwigExtension::class => TwigExtension::class,
        ],
        'factories' => [
            AuthListener::class => AuthListenerFactory::class,
            OpgApiService::class => OpgApiServiceFactory::class,
            SiriusApiService::class => SiriusApiServiceFactory::class,
            LoggerInterface::class => LoggerFactory::class,
        ],
    ],
    'zend_twig'       => [
        'extensions' => [
            TwigExtension::class,
        ],
    ],
];
