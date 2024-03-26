<?php

declare(strict_types=1);

namespace Application;

use Application\Factories\LoggerFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
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
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'method' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/method',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'method',
                    ],
                ],
            ],
            'details' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/details',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'details',
                    ],
                ],
            ],
            'address_verification' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/address_verification',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'addressVerification',
                    ],
                ],
            ],
            'list_lpas' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/list_lpas',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'listLpas',
                    ],
                ],
            ],
            'validate_nino' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/validate_nino',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'validateNino',
                    ],
                ],
            ],
            'validate_driving_licence' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/validate_driving_licence',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'validateDrivingLicence',
                    ],
                ],
            ],
            'validate_passport' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/validate_passport',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'validatePassport',
                    ],
                ],
            ],
            'get_kbv_questions' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/cases[/:action]/kbv-questions',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'getKbvQuestions',
                    ],
                ],
            ],
            'check_kbv_answers' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/cases[/:action]/kbv-answers',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'checkKbvAnswers',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\IdentityController::class => InvokableFactory::class
        ],
    ],
    'service_manager' => [
        'factories' => [
            LoggerInterface::class => LoggerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_map'             => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            '404'                     => __DIR__ . '/../view/error/error.json',
            'error'                   => __DIR__ . '/../view/error/error.json',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
