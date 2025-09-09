<?php

declare(strict_types=1);

namespace Application\HMPO\HmpoApi\DTO;

use Application\HMPO\HmpoApi\HmpoApiException;

class ValidatePassportResponseDTO
{
    private bool $validationResult;
    private bool $passportCancelled;
    private bool $passportLostStolen;

    public function __construct(array $response)
    {
        try {
            $results  = $response['data']['validatePassport'];
            $this->validationResult = $results['validationResult'];
            $this->passportCancelled = $results['passportCancelled'] ?? false;
            $this->passportLostStolen = $results['passportLostStolen'] ?? false;
        } catch (\Exception $exception) {
            throw new HmpoApiException($exception->getMessage());
        }
    }

    public function validationResult(): bool
    {
        return $this->validationResult;
    }

    public function passportCancelled(): bool
    {
        return $this->passportCancelled;
    }

    public function passportLostStolen(): bool
    {
        return $this->passportLostStolen;
    }

    public function toArray(): array
    {
        return [
            'validationResult' => $this->validationResult(),
            'passportCancelled' => $this->passportCancelled(),
            'passportLostStolen' => $this->passportLostStolen(),
        ];
    }

    public function isValid(): bool
    {
        if ($this->validationResult() === true && $this->passportCancelled() === false) {
            return true;
        }
        return false;
    }
}
