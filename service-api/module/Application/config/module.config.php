<?php

declare(strict_types=1);

namespace Application;

use Application\Auth\Listener as AuthListener;
use Application\Auth\ListenerFactory as AuthListenerFactory;
use Application\Aws\DynamoDbClientFactory;
use Application\Aws\EventBridgeClientFactory;
use Application\Aws\Secrets\AwsSecretsCache;
use Application\Aws\Secrets\AwsSecretsCacheFactory;
use Application\Aws\SsmClientFactory;
use Application\Aws\SsmHandler;
use Application\Aws\SsmHandlerFactory;
use Application\Controller\Factory\HealthcheckControllerFactory;
use Application\DrivingLicense\ValidatorFactory as LicenseFactory;
use Application\DrivingLicense\ValidatorInterface as LicenseInterface;
use Application\DWP\DwpApi\DwpApiService;
use Application\DWP\Factories\DwpApiServiceFactory;
use Application\DWP\Factories\DwpAuthApiServiceFactory;
use Application\Experian\Crosscore\AuthApi\AuthApiService;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use Application\Experian\IIQ\AuthManager;
use Application\Experian\IIQ\AuthManagerFactory;
use Application\Experian\IIQ\Soap\IIQClient;
use Application\Experian\IIQ\Soap\IIQClientFactory;
use Application\Experian\IIQ\Soap\WaspClient;
use Application\Experian\IIQ\Soap\WaspClientFactory;
use Application\Factories\EventSenderFactory;
use Application\Factories\ExperianCrosscoreAuthApiServiceFactory;
use Application\Factories\ExperianCrosscoreFraudApiServiceFactory;
use Application\Factories\LoggerFactory;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\KBV\KBVServiceFactory;
use Application\KBV\KBVServiceInterface;
use Application\Nino\ValidatorFactory as NinoValidatorFactory;
use Application\Nino\ValidatorInterface as NinoValidatorInterface;
use Application\Passport\ValidatorFactory as PassportValidatorFactory;
use Application\Passport\ValidatorInterface as PassportValidatorInterface;
use Application\Sirius\EventSender;
use Application\Yoti\YotiServiceFactory;
use Application\Yoti\YotiServiceInterface;
use Application\DWP\AuthApi\AuthApiService as DwpAuthApiService;
use Aws\DynamoDb\DynamoDbClient;
use Aws\EventBridge\EventBridgeClient;
use Aws\Ssm\SsmClient;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Lcobucci\Clock\SystemClock;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

$tableName = getenv("AWS_DYNAMODB_TABLE_NAME");

if (! is_string($tableName) || empty($tableName)) {
    $tableName = 'identity-verify';
}

return [
    'router' => [
        'routes' => [
            'health_check' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/health-check',
                    'defaults' => [
                        'controller' => Controller\HealthcheckController::class,
                        'action' => 'healthCheck',
                    ],
                ],
            ],
            'health_check_service' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/health-check/service',
                    'defaults' => [
                        'controller' => Controller\HealthcheckController::class,
                        'action' => 'healthCheckService',
                    ],
                ],
            ],
            'health_check_dependencies' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/health-check/dependencies',
                    'defaults' => [
                        'controller' => Controller\HealthcheckController::class,
                        'action' => 'healthCheckDependencies',
                    ],
                ],
            ],
            'details' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/identity/details',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'details',
                    ],
                ],
            ],
            'findbyname' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/identity/findbyname',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'findByName',
                    ],
                ],
            ],
            'findbyidnumber' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/identity/findbyidnumber',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'findByIdNumber',
                    ],
                ],
            ],
            'create_case' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/identity/create',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'create',
                    ],
                ],
            ],
            'validate_nino' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/identity/:uuid/validate_nino',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'validateNino',
                    ],
                ],
            ],
            'validate_driving_licence' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/identity/validate_driving_licence',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'validateDrivingLicence',
                    ],
                ],
            ],
            'validate_passport' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/identity/validate_passport',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'validatePassport',
                    ],
                ],
            ],
            'get_kbv_questions' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/[:uuid/]kbv-questions',
                    'defaults' => [
                        'controller' => Controller\KbvController::class,
                        'action' => 'getQuestions',
                    ],
                ],
            ],
            'check_kbv_answers' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/kbv-answers',
                    'defaults' => [
                        'controller' => Controller\KbvController::class,
                        'action' => 'checkAnswers',
                    ],
                ],
            ],
            'create' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/create',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'create',
                    ],
                ],
            ],
            'update_case' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/update/:uuid',
                    'verb' => 'patch',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'update',
                    ],
                ],
            ],
            'update_case_method' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/update-method',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'updatedMethod',
                    ],
                ],
            ],
            'find_postoffice_branches' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/counter-service/branches',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action' => 'findPostOffice',
                    ],
                ],
            ],
            'create_yoti_session' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/counter-service/:uuid/create-session',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action' => 'createSession',
                    ],
                ],
            ],
            'retrieve_yoti_status' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/counter-service/:uuid/retrieve-status',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action' => 'getSessionStatus',
                    ],
                ],
            ],
            'yoti_notification' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/counter-service/notification',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action' => 'notification',
                    ],
                ],
            ],
            'add_selected_postoffice' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/add-selected-postoffice',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'addSelectedPostoffice',
                    ],
                ],
            ],
            'estimate_postoffice_deadline' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/counter-service/:uuid/estimate-postoffice-deadline',
                    'defaults' => [
                        'controller' => Controller\YotiController::class,
                        'action' => 'estimatePostOfficeDeadline',
                    ],
                ],
            ],

            'change_case_lpa' => [
                'type' => Segment::class,
                'verb' => 'put',
                'options' => [
                    'route' => '/cases/:uuid/lpas/:lpa',
                ],
                'child_routes' => [
                    'put' => [
                        'type' => Method::class,
                        'options' => [
                            'verb' => 'put',
                            'defaults' => [
                                'controller' => Controller\IdentityController::class,
                                'action' => 'addCaseLpa',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                    'delete' => [
                        'type' => Method::class,
                        'options' => [
                            'verb' => 'delete',
                            'defaults' => [
                                'controller' => Controller\IdentityController::class,
                                'action' => 'removeCaseLpa',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
            'search_address_by_postcode' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/search-address-by-postcode/:postcode',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'searchAddressByPostcode',
                    ],
                ],
            ],
            'save_address_to_case' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/save-address-to-case',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'saveAddressToCase',
                    ],
                ],
            ],
            'update_professional_address' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/update-professional-address',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'saveProfessionalAddressToCase',
                    ],
                ],
            ],
            'complete_document' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/complete-document',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'setDocumentComplete',
                    ],
                ],
            ],
            'update_dob' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/update-dob/:dob',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'updateDob',
                    ],
                ],
            ],
            'update_name' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/update-name',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'updateName',
                    ],
                ],
            ],
            'update_cp_po_id' => [
                'type' => Segment::class,
                'verb' => 'put',
                'options' => [
                    'route' => '/cases/:uuid/update-cp-po-id',
                ],
                'child_routes' => [
                    'put' => [
                        'type' => Method::class,
                        'options' => [
                            'verb' => 'put',
                            'defaults' => [
                                'controller' => Controller\IdentityController::class,
                                'action' => 'updateCpPoId',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
            'save_case_progress' => [
                'type' => Segment::class,
                'verb' => 'put',
                'options' => [
                    'route' => '/cases/:uuid/save-case-progress',
                ],
                'child_routes' => [
                    'put' => [
                        'type' => Method::class,
                        'options' => [
                            'verb' => 'put',
                            'defaults' => [
                                'controller' => Controller\IdentityController::class,
                                'action' => 'saveCaseProgress',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
            'request_fraud_check' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/request-fraud-check',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'requestFraudCheck',
                    ],
                ],
            ],
            'service_availability' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/service-availability',
                    'defaults' => [
                        'controller' => Controller\HealthcheckController::class,
                        'action' => 'serviceAvailability',
                    ],
                ],
            ],
            'save_case_assistance' => [
                'type' => Segment::class,
                'verb' => 'put',
                'options' => [
                    'route' => '/cases/:uuid/save-case-assistance',
                ],
                'child_routes' => [
                    'put' => [
                        'type' => Method::class,
                        'options' => [
                            'verb' => 'put',
                            'defaults' => [
                                'controller' => Controller\IdentityController::class,
                                'action' => 'saveCaseAssistance',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
            'send_identity_check' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cases/:uuid/send-identity-check',
                    'defaults' => [
                        'controller' => Controller\IdentityController::class,
                        'action' => 'sendIdentityCheck',
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
            Controller\IdentityController::class => LazyControllerAbstractFactory::class,
            Controller\YotiController::class => LazyControllerAbstractFactory::class,
            Controller\HealthcheckController::class => HealthcheckControllerFactory::class,
        ],
    ],

    'service_manager' => [
        'invokables' => [
        ],
        'factories' => [
            DynamoDbClient::class => DynamoDbClientFactory::class,
            SsmClient::class => SsmClientFactory::class,
            SsmHandler::class => SsmHandlerFactory::class,
            EventBridgeClient::class => EventBridgeClientFactory::class,
            DataQueryHandler::class => fn (ServiceLocatorInterface $serviceLocator) => new DataQueryHandler(
                $serviceLocator->get(DynamoDbClient::class),
                $tableName
            ),
            DataWriteHandler::class => fn (ServiceLocatorInterface $serviceLocator) => new DataWriteHandler(
                $serviceLocator->get(DynamoDbClient::class),
                $tableName,
                $serviceLocator->get(LoggerInterface::class),
                $serviceLocator->get(ClockInterface::class),
            ),
            LoggerInterface::class => LoggerFactory::class,
            LicenseInterface::class => LicenseFactory::class,
            PassportValidatorInterface::class => PassportValidatorFactory::class,
            KBVServiceInterface::class => KBVServiceFactory::class,
            AwsSecretsCache::class => AwsSecretsCacheFactory::class,
            YotiServiceInterface::class => YotiServiceFactory::class,
            FraudApiService::class => ExperianCrosscoreFraudApiServiceFactory::class,
            AuthApiService::class => ExperianCrosscoreAuthApiServiceFactory::class,
            EventSender::class => EventSenderFactory::class,
            WaspClient::class => WaspClientFactory::class,
            IIQClient::class => IIQClientFactory::class,
            AuthManager::class => AuthManagerFactory::class,
            ClockInterface::class => fn () => SystemClock::fromSystemTimezone(),
            DwpAuthApiService::class => DwpAuthApiServiceFactory::class,
            DwpApiService::class => DwpApiServiceFactory::class,
            AuthListener::class => AuthListenerFactory::class,
        ],
    ],
    'listeners' => [
        AuthListener::class,
    ],
    'view_manager' => [
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            '404' => __DIR__ . '/../view/error/error.json',
            'error' => __DIR__ . '/../view/error/error.json',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'opg_settings' => [
        'identity_documents' => [
            'PASSPORT' => "Passport",
            'DRIVING_LICENCE' => 'Driving licence',
            'NATIONAL_INSURANCE_NUMBER' => 'National Insurance number',
        ],
        'identity_methods' => [
            'POST_OFFICE' => 'Post Office',
            'VOUCHING' => 'Have someone vouch for the identity of the donor',
            'COURT_OF_PROTECTION' => 'Court of protection',
        ],
        'identity_services' => [
            'EXPERIAN' => 'Experian',
        ],
        'banner_messages' => [
            'NODECISION' => 'The donor cannot ID over the phone due to a lack of available security questions ' .
                'or failure to answer them correctly on a previous occasion.',
            'DONOR_STOP' => 'The donor cannot ID over the phone or have someone vouch for them due to a lack of ' .
                'available information from Experian or a failure to answer the security questions correctly ' .
                'on a previous occasion.',
            'CP_STOP' => 'The certificate provider cannot ID over the phone due to a lack of available information ' .
                'from Experian or a failure to answer the security questions correctly on a previous occasion.',
            'VOUCHER_STOP' => 'The person vouching cannot ID over the phone due to a lack of available information ' .
                'from Experian or a failure to answer the security questions correctly on a previous occasion.',
            'LOCKED_ID_SUCCESS' => 'The %s has already proved their identity over the ' .
                'phone with a valid document',
            'LOCKED' => 'The %s cannot prove their identity over the phone because they have tried before ' .
                'and their details did not match the document provided.',
            'LOCKED_SUCCESS' => 'The identity check has already been completed',
        ],
        'person_type_labels' => [
            'donor' => 'donor',
            'certificateProvider' => 'certificate provider',
            'voucher' => 'person vouching'
        ]
    ]
];
