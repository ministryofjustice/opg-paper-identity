<?php

declare(strict_types=1);

namespace Application\Aws\Secrets;

use Aws\SecretsManager\SecretsManagerClient;
use Laminas\Cache\Storage\Adapter\Apcu;
use Laminas\Cache\Storage\Plugin\ExceptionHandler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AwsSecretsCacheFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AwsSecretsCache
    {
        $storage  = new Apcu();
        $plugin = new ExceptionHandler();
        $plugin->getOptions()->setThrowExceptions(false);
        $storage->addPlugin($plugin);

        /** @var array<mixed> $config */
        $config = $container->get('Config');

        $secretsManagerClient = new SecretsManagerClient($config['aws']);

        return new AwsSecretsCache($config['secrets_prefix'], $storage, $secretsManagerClient);
    }
}
