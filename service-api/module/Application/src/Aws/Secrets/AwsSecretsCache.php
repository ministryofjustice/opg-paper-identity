<?php

declare(strict_types=1);

namespace Application\Aws\Secrets;

use Application\Aws\Secrets\Exception\InvalidSecretResponseException;
use Aws\SecretsManager\SecretsManagerClient;
use Laminas\Cache\Storage\StorageInterface;

class AwsSecretsCache
{
    private const NAMESPACE_AWS = 'aws';

    public function __construct(
        private readonly string $environment,
        private readonly StorageInterface $storage,
        private readonly SecretsManagerClient $client
    ) {
    }

    public function getValue(string $name): string
    {
        $name = $this->environment . '/' . $name;
        $key = self::NAMESPACE_AWS . ':' . $name;

        if ($this->storage->hasItem($key)) {
            return $this->storage->getItem($key);
        }

        $value = $this->getValueFromAWS($name);
        $this->storage->setItem($key, $value);
        return $value;
    }

    protected function getValueFromAWS(string $name): string
    {
        $result = $this->client->getSecretValue([
            'SecretId' => $name,
        ]);

        $secret = false;
        if (isset($result['SecretString'])) {
            $secret = $result['SecretString'];
        } elseif (isset($result['SecretBinary'])) {
            $secret = base64_decode($result['SecretBinary']);
        }

        if ($secret === false) {
            throw new InvalidSecretResponseException('No value returned for requested key ' . $name);
        }

        return $secret;
    }

    public function clearCache(string $name): bool
    {
        $key = self::NAMESPACE_AWS . ':' . $name;
        if ($this->storage->hasItem($key)) {
            return $this->storage->removeItem($key);
        }
        return false;
    }
}
