<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\PersonType;
use Application\Exceptions\LpaTypeException;

class LpaStatusTypeHelper
{
    private array $lpaStatusTypes = [
        'draft' => 'Draft',
        'in-progress' => 'In progress',
        'statutory-waiting-period' => 'Statutory waiting period',
        'registered' => 'Registered',
        'suspended' => 'Suspended',
        'do-not-register' => 'Do not register',
        'expired' => 'Expired',
        'cannot-register' => 'Cannot register',
        'cancelled' => 'Cancelled',
        'de-registered' => 'De-registered',
    ];

    private array $lpaPermissions = [
        PersonType::Donor->value => [
            'draft',
            'in-progress',
            'statutory-waiting-period',
            'do-not-register'
        ],
        PersonType::CertificateProvider->value => [
            'in-progress',
            'statutory-waiting-period',
            'do-not-register'
        ],
        PersonType::Voucher->value => [
            'in-progress',
            'statutory-waiting-period',
            'do-not-register'
        ]
    ];

    private bool $startable;

    private string $status;

    public function __construct(
        private array $lpa,
        private PersonType $personType = PersonType::Donor
    ) {
        // TODO: what about PersonType::Voucher
        if (isset($lpa['opg.poas.lpastore'][$this->personType->value]['identityCheck'])) {
            $this->startable = false;
            $this->status = 'registered';
        } else {
            $this->setStatus();
            $this->setCanStart();
        }
    }

    private function setStatus(): void
    {
        if (
            ! array_key_exists('opg.poas.lpastore', $this->lpa) ||
            empty($this->lpa['opg.poas.lpastore'])
        ) {
            $this->status = 'draft';
        } else {
            $this->status = $this->lpa['opg.poas.lpastore']['status'];
            if (! array_key_exists($this->status, $this->lpaStatusTypes)) {
                throw new LpaTypeException($this->status . " is not a valid LPA status.");
            }
        }
    }

    private function setCanStart(): void
    {
        try {
            $this->startable = in_array($this->status, $this->lpaPermissions[$this->personType->value]);
        } catch (LpaTypeException $exception) {
            $this->startable = false;
        }
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isStartable(): bool
    {
        return $this->startable;
    }
}
