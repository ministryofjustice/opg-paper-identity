<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DwpApiException;

class AmbiguousCitizenResponseDTO
{
    private array $errors;
    public function __construct(array $response)
    {
        try {
            $this->errors = $response['errors'];
        } catch (\Exception $exception) {
            throw new DwpApiException($exception->getMessage());
        }
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'errors' => $this->errors()
        ];
    }
}
