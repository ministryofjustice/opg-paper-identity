<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

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
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\IdentityController::class => InvokableFactory::class
        ],
    ],

    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
