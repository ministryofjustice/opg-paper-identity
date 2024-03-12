<?php

declare(strict_types=1);

namespace Application;

use Application\Factories\OpgApiServiceFactory;
use Application\Services\OpgApiService;
use Application\Views\TwigExtension;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;

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
            'donor_lpa_check' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/donor-lpa-check',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'donorLpaCheck',
                    ],
                ],
            ],
            'donor_id_check' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/donor-id-check',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'donorIdCheck',
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
            'identity_verification' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/identity_verification',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'identityVerification',
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
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.twig',
            'application/index/index' => __DIR__ . '/../view/application/index/index.twig',
            'error/404'               => __DIR__ . '/../view/error/404.twig',
            'error/403'               => __DIR__ . '/../view/error/403.twig',
            'error/feature403'        => __DIR__ . '/../view/error/feature403.twig',
            'error/index'             => __DIR__ . '/../view/error/index.twig',
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
            OpgApiService::class => OpgApiServiceFactory::class,
        ],
    ],
    'zend_twig'       => [
        'extensions' => [
            TwigExtension::class,
        ],
    ],
];
