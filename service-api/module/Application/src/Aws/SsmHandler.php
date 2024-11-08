<?php

declare(strict_types=1);

namespace Application\Aws;

use Aws\Ssm\SsmClient;
use Psr\Log\LoggerInterface;

class SsmHandler
{
    public function __construct(
        private readonly SsmClient $ssmClient,
        private readonly LoggerInterface $log
    ) {
    }

    /**
     * @throws \Exception
     */
    public function getJsonParameter(string $paramName): array
    {
        $parameter = $this->ssmClient->getParameter([
            'Name' => $paramName
        ])->toArray();

        try {
            return json_decode($parameter['Parameter']['Value'], true);
        } catch (\Exception $exception) {
            $this->log->error($exception->getMessage());
            throw $exception;
        }
    }
}
