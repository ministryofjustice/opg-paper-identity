<?php

declare(strict_types=1);

namespace Application\Aws\Secrets;

use Application\Aws\Secrets\Exception\InvalidSecretsResponseException;
use Aws\SecretsManager\SecretsManagerClient;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;

class AwsSecretsCache
{
    private const NAMESPACE_AWS = 'aws';

    public function __construct(
        private readonly string $prefix,
        private readonly StorageInterface $storage,
        private readonly SecretsManagerClient $client
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidSecretsResponseException
     */
    public function getValue(string $name): string
    {
        $name = $this->prefix . '/' . $name;
        $key = self::NAMESPACE_AWS . ':' . $name;

        if ($this->storage->hasItem($key)) {
            return $this->storage->getItem($key);
        }

        $value = $this->getValueFromAWS($name);
        $this->storage->setItem($key, $value);
        return $value;
    }

    /**
     * @psalm-suppress NullableReturnStatement
     * @param string $name
     * @return string
     * @throws InvalidSecretsResponseException
     */
    protected function getValueFromAWS(string $name): string
    {
        $result = $this->client->getSecretValue([
            'SecretId' => $name,
        ]);
        $secret = false;
        if (isset($result['SecretString'])) {
            $secret = $result['SecretString'];
        } elseif (isset($result['SecretBinary'])) {
            /** @var string $secretBinary */
            $secretBinary = $result['SecretBinary'];
            $secret = base64_decode($secretBinary);
        }

        if ($secret === false) {
            throw new InvalidSecretsResponseException('No value returned for requested key ' . $name);
        }

        return $secret;
    }
}
