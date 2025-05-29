<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaStatusType;
use Application\Enums\PersonType;
use Application\Exceptions\LpaTypeException;

class LpaStatusTypeHelper
{
    private array $lpaPermissions = [
        PersonType::Donor->value => [
            LpaStatusType::Draft,
            LpaStatusType::InProgress,
            LpaStatusType::StatutoryWaitingPeriod,
            LpaStatusType::DoNotRegister
        ],
        PersonType::CertificateProvider->value => [
            LpaStatusType::InProgress,
            LpaStatusType::StatutoryWaitingPeriod,
            LpaStatusType::DoNotRegister
        ],
        PersonType::Voucher->value => [
            LpaStatusType::InProgress,
            LpaStatusType::StatutoryWaitingPeriod,
            LpaStatusType::DoNotRegister
        ]
    ];

    private bool $startable;
    private LpaStatusType $status;

    public function __construct(
        private array $lpa,
        private PersonType $personType = PersonType::Donor
    ) {
        $personTypeValue = $personType === PersonType::CertificateProvider ? 'certificateProvider' : 'donor';

        if (isset($lpa['opg.poas.lpastore'][$personTypeValue]['identityCheck'])) {
            $this->startable = false;
            $this->status = LpaStatusType::Registered;
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
            $this->status = LpaStatusType::Draft;
        } else {
            try {
                $this->status = LpaStatusType::from($this->lpa['opg.poas.lpastore']['status']);
            } catch (\ValueError) {
                throw new LpaTypeException($this->lpa['opg.poas.lpastore']['status'] . " is not a valid LPA status.");
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
     * @return LpaStatusType
     */
    public function getStatus(): LpaStatusType
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
