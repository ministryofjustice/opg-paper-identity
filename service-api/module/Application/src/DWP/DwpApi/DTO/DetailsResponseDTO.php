<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DwpApiException;

class DetailsResponseDTO
{
    private string $nino;
    private string $guid;

    public function __construct(array $response)
    {
        try {
            $this->nino = $response['data']['attributes']['nino'];
            $this->guid = $response['data']['attributes']['guid'];
        } catch (\Exception $exception) {
            throw new DwpApiException($exception->getMessage());
        }
    }

    public function guid(): string
    {
        return $this->guid;
    }

    public function nino(): string
    {
        return $this->nino;
    }
    public function toArray(): array
    {
        return [
            'guid' => $this->guid(),
            'nino' => $this->nino(),
        ];
    }
}
