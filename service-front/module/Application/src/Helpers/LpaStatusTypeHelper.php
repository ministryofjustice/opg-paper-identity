<?php

declare(strict_types=1);

namespace Application\Helpers;

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
        'donor' => [
            'draft',
            'in-progress',
            'statutory-waiting-period',
            'do-not-register'
        ],
        'certificateProvider' => [
            'in-progress',
            'statutory-waiting-period',
            'do-not-register'
        ],
        'voucher' => [
            'in-progress',
            'statutory-waiting-period',
            'do-not-register'
        ]
    ];

    private array $personTypes = [
        'donor' => 'Donor',
        'certificateProvider' => 'Certificate Provider',
        'voucher' => 'Voucher'
    ];

    private bool $startable;

    private string $status;

    public function __construct(
        private array $lpa,
        private string $personType = 'donor'
    ) {
        if (isset($lpa['opg.poas.lpastore'][$this->personType]['identityCheck'])) {
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
        if (! array_key_exists($this->personType, $this->personTypes)) {
            throw new LpaTypeException('Person type "' . $this->personType . '" is not valid');
        }

        try {
            $this->startable = in_array($this->status, $this->lpaPermissions[$this->personType]);
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
