<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class PassportDateValidator extends AbstractValidator
{
    private string $expiryAllowance = '+18 month';

    public function setOptions(mixed $options = []): self
    {
        /**
         * @psalm-suppress InvalidArrayAccess
         */
        if (isset($options['expiry_allowance'])) {
            $this->expiryAllowance = $options['expiry_allowance'];
        }

        return $this;
    }

    public function isValid($value): bool
    {
        $this->setValue($value);

        return $this->validity($value);
    }

    private function validity(string $date): bool
    {
        try {
            $now = time();
            $expiryDate = strtotime($date);
            if ($expiryDate === false) {
                return false;
            }

            $effectiveExpiryTime = strtotime($this->expiryAllowance, $expiryDate);
            if ($effectiveExpiryTime === false) {
                return false;
            }

            $effectiveExpiry = date('Y-m-d', $effectiveExpiryTime);

            return $now < strtotime($effectiveExpiry);
        } catch (\Exception $exception) {
            return false;
        }
    }
}
