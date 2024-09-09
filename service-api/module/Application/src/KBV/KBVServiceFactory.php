<?php

declare(strict_types=1);

namespace Application\KBV;

use Application\Experian\IIQ\KBVService;
use Application\Mock\KBV\KBVService as MockKBVService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class KBVServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<mixed>|null $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): KBVServiceInterface
    {
        /** @var bool $useMock */
        $useMock = getenv("MOCK_KBV_API");
        if ($useMock) {
            //return new MockKBVService();
        }

        return $container->get(KBVService::class);
    }
}
