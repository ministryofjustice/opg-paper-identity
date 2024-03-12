<?php

declare(strict_types=1);

namespace Application;

use Application\Aws\DynamoDbClientFactory;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Aws\DynamoDb\DynamoDbClient;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;

$tableName = getenv("PAPER_ID_BACK_DATA_TABLE_NAME");

if (! is_string($tableName) || empty($tableName)) {
    $tableName = 'identity-verify';
}

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
            'testdata' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/testdata',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'testdata',
                    ],
                ],
            ],
            'findbyname' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/findbyname',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'findByName',
                    ],
                ],
            ],
            'findbyidnumber' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/findbyidnumber',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'findByIdNumber',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'abstract_factories' => [
            LazyControllerAbstractFactory::class,
        ],
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\IdentityController::class => LazyControllerAbstractFactory::class
        ],
    ],

    'service_manager' => [
        'invokables' => [

        ],
        'factories' => [
            DynamoDbClient::class => DynamoDbClientFactory::class,
            DataQueryHandler::class => fn(ServiceLocatorInterface $serviceLocator) => new DataQueryHandler(
                $serviceLocator->get(DynamoDbClient::class),
                $tableName
            ),
            DataImportHandler::class => fn(ServiceLocatorInterface $serviceLocator) => new DataImportHandler(
                $serviceLocator->get(DynamoDbClient::class),
                $tableName
            )
        ],
    ],

    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map'             => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack'      => [
            __DIR__ . '/../view',
        ],
    ],
];
