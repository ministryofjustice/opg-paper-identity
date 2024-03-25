<?php

declare(strict_types=1);

namespace Application;

use Application\Factories\LoggerFactory;
use Application\Factories\NinoAPIServiceFactory;
use Application\Services\Contract\NINOServiceInterface;
use Application\Services\MockNinoService;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
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
                        'action'     => 'verifyNino',
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
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\IdentityController::class => LazyControllerAbstractFactory::class
        ],
    ],
    'service_manager' => [
        'factories' => [
            LoggerInterface::class => LoggerFactory::class,
            NINOServiceInterface::class => NinoAPIServiceFactory::class
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
