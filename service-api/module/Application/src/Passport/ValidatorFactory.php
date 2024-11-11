<?php

declare(strict_types=1);

namespace Application\Passport;

use Application\Passport\ValidatorInterface;
use Application\Passport\Validator;
use Application\Mock\Passport\Validator as MockValidator;
use GuzzleHttp\Client;
use RuntimeException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ValidatorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ValidatorInterface
    {
        /** @var bool $useMock */
        $useMock = getenv("MOCK_PASSPORT_API");
        if ($useMock) {
            return new MockValidator();
        }

        $baseUri = getenv("PASSPORT_API_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("PASSPORT_BASE_URL is empty");
        }

        $client = new Client(['base_uri' => $baseUri]);

        return new Validator(
            $client
        );
    }
}
