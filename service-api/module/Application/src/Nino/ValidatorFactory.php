<?php

declare(strict_types=1);

namespace Application\Nino;

use Application\Nino\ValidatorInterface;
use Application\Nino\Validator;
use Application\Mock\Nino\Validator as MockValidator;
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
        $useMock = getenv("MOCK_NINO_API");
        if ($useMock) {
            return new MockValidator();
        }

        $baseUri = getenv("NINO_API_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("NINO_BASE_URL is empty");
        }

        $client = new Client(['base_uri' => $baseUri]);

        return new Validator(
            $client
        );
    }
}
