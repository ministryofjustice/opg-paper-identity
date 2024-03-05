<?php

declare(strict_types=1);

namespace Application;

use Application\Factories\OpgApiServiceFactory;
use Application\Services\OpgApiService;
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
            'test' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/test',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'application',
                    ],
                ],
            ],
            'donor_lpa_check' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/donor-lpa-check',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'donorLpaCheckAction',
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
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'application',
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
        'factories' => [
            OpgApiService::class => OpgApiServiceFactory::class,
        ],
    ],
];
