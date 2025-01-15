<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DwpApiException;

class CitizenResponseDTO
{
    private string $id;

    private string $type;

    private string $matchScenario;

    private string $version;

    private array $raw;
    public function __construct(array $response)
    {
        try {
            $this->id = $response['data']['id'];
            $this->type = $response['data']['type'];
            $this->matchScenario = $response['data']['attributes']['matchingScenario'];
            $this->version = $response['jsonapi']['version'];
            $this->raw = $response;
        } catch (\Exception $exception) {
            throw new DwpApiException($exception->getMessage());
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function matchScenario(): string
    {
        return $this->matchScenario;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function raw(): array
    {
        return $this->raw;
    }
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'type' => $this->type(),
            'matchScenario' => $this->matchScenario(),
            'version' => $this->version(),
        ];
    }
}
