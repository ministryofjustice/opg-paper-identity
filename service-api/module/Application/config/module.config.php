<?php

declare(strict_types=1);

namespace Application;

use Application\Aws\DynamoDbClientFactory;
use Application\Factories\LoggerFactory;
use Application\KBV\KBVServiceFactory;
use Application\KBV\KBVServiceInterface;
use Application\Nino\ValidatorFactory as NinoValidatorFactory;
use Application\Nino\ValidatorInterface as NinoValidatorInterface;
use Application\DrivingLicense\ValidatorFactory as LicenseFactory;
use Application\DrivingLicense\ValidatorInterface as LicenseInterface;
use Application\Passport\ValidatorInterface as PassportValidatorInterface;
use Application\Passport\ValidatorFactory as PassportValidatorFactory;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Passport\ValidatorInterface;
use Application\Yoti\YotiServiceFactory;
use Application\Yoti\YotiServiceInterface;
use Aws\DynamoDb\DynamoDbClient;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Log\LoggerInterface;

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

            'create_case' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/identity/create',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'create',
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
                    'route'    => '/cases/[:uuid/]kbv-questions',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'getKbvQuestions',
                    ],
                ],
            ],
            'check_kbv_answers' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/cases/:uuid/kbv-answers',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'checkKbvAnswers',
                    ],
                ],
            ],
            'create' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/cases/create',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'create',
                    ],
                ],
            ],
            'find_lpa' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/cases/:uuid/find-lpa/:lpa',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'findLpa',
                    ],
                ],
            ],
            'update_case_method' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/cases/:uuid/update-method',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'updatedMethod',
                    ],
                ],
            ],
            'find_postoffice_branches' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/counter-service/branches',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action'     => 'findPostOffice',
                    ],
                ],
            ],
            'create_yoti_session' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/counter-service/create-session',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action'     => 'createSession',
                    ],
                ],
            ],
            'retrieve_yoti_status' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/counter-service[/:sessionId]/retrieve-status',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action'     => 'getSessionStatus',
                    ],
                ],
            ],
            'retrieve_pdf_letter' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/counter-service[/:sessionId]/retrieve-letter',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action'     => 'getPDFLetter',
                    ],
                ]
            ],

            'add_case_lpa' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/cases/:uuid/add=lpa/:lpa',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action'     => 'addCaseLpa',

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
                $tableName,
                $serviceLocator->get(LoggerInterface::class)
            ),
            LoggerInterface::class => LoggerFactory::class,
            NinoValidatorInterface::class => NinoValidatorFactory::class,
            LicenseInterface::class => LicenseFactory::class,
            PassportValidatorInterface::class => PassportValidatorFactory::class,
            KBVServiceInterface::class => KBVServiceFactory::class,
            YotiServiceInterface::class => YotiServiceFactory::class
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
