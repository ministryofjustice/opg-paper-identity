<?php

declare(strict_types=1);

namespace Application;

use Application\Auth\JwtGenerator;
use Application\Auth\JwtGeneratorFactory;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Factories\LoggerFactory;
use Application\Factories\OpgApiServiceFactory;
use Application\Factories\SiriusApiServiceFactory;
use Application\Handler\PostOffice\FindPostOfficeBranchHandlerFactory;
use Application\Helpers\RouteHelper;
use Application\Helpers\RouteHelperFactory;
use Application\Mezzio\ErrorResponseGeneratorFactory;
use Application\Mezzio\LoggingErrorListenerDelegatorFactory;
use Application\Middleware\AuthMiddleware;
use Application\Middleware\AuthMiddlewareFactory;
use Application\PostOffice\DocumentTypeRepository;
use Application\PostOffice\DocumentTypeRepositoryFactory;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Application\Views\TwigExtension;
use Application\Views\TwigExtensionFactory;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Lcobucci\Clock\SystemClock;
use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigRenderer;
use Mezzio\Twig\TwigRendererFactory;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Twig\Extension\DebugExtension;

$yotiSupportedDocs = file_get_contents(__DIR__ . '/yoti-supported-documents.json');

return [
    'templates' => [
        'extension' => 'twig',
        'layout' => 'layout/plain',
        'paths' => [
            '__main__' => [__DIR__ . '/../view'],
            'layout' => [__DIR__ . '/../view/layout'],
            'error' => [__DIR__ . '/../view/error'],
        ],
    ],
    'dependencies' => [
        'aliases' => [
            Contracts\OpgApiServiceInterface::class => Services\OpgApiService::class,
            TemplateRendererInterface::class => TwigRenderer::class,
        ],
        'factories' => [
            AuthMiddleware::class => AuthMiddlewareFactory::class,
            ClockInterface::class => fn () => SystemClock::fromSystemTimezone(),
            JwtGenerator::class => JwtGeneratorFactory::class,
            LoggerInterface::class => LoggerFactory::class,
            OpgApiService::class => OpgApiServiceFactory::class,
            RouteHelper::class => RouteHelperFactory::class,
            SiriusApiService::class => SiriusApiServiceFactory::class,
            TwigExtension::class => TwigExtensionFactory::class,
            DocumentTypeRepository::class => DocumentTypeRepositoryFactory::class,
            TwigRenderer::class => TwigRendererFactory::class,
            ErrorResponseGenerator::class => ErrorResponseGeneratorFactory::class,
            // Handlers
            Handler\PostOffice\FindPostOfficeBranchHandler::class => FindPostOfficeBranchHandlerFactory::class,
        ],
        'delegators' => [
            ErrorHandler::class => [
                LoggingErrorListenerDelegatorFactory::class,
            ],
        ],
    ],
    'twig' => [
        'extensions' => [
            TwigExtension::class,
            DebugExtension::class,
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
