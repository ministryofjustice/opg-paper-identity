<?php

declare(strict_types=1);

namespace Application\Fixtures;

use Aws\Ssm\SsmClient;

class SsmHandler
{
    public function __construct(private readonly SsmClient $ssmClient)
    {
    }

    public function getParameter(string $paramName): array
    {
        $parameter = $this->ssmClient->getParameter([
            'Name' => $paramName
        ])->toArray();

        if (is_string($parameter['Parameter']['Value'])) {
            return json_decode($parameter['Parameter']['Value'], true);
        } else {
            return $parameter['Parameter']['Value'];
        }
    }
}
