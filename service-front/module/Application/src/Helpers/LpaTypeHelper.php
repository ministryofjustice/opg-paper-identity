<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Exceptions\LpaTypeException;

class LpaTypeHelper
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

    private bool $canStart = true;

    private string $status = 'draft';

    public function __construct(
        private array $lpa,
        private string $personType = 'donor'
    ) {
        if (isset($lpa['opg.poas.lpastore']['donor']['identityCheck'])) {
            $this->canStart = false;
            $this->status = 'registered';
        } else {
            $this->setStatus();
            $this->setCanStart();
        }
    }

    private function setStatus(): void
    {
        if (! array_key_exists('opg.poas.lpastore', $this->lpa)) {
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
            throw new LpaTypeException($this->personType . " is not a valid Person Type");
        }

        try {
            $this->canStart = in_array($this->status, $this->lpaPermissions[$this->personType]);
        } catch (LpaTypeException $exception) {
            $this->canStart = false;
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
    public function isCanStart(): bool
    {
        return $this->canStart;
    }
}
