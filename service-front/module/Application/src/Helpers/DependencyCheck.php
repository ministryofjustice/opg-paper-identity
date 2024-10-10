<?php

declare(strict_types=1);

namespace Application\Helpers;

use Aws\Ssm\SsmClient;
use Application\Enums\IdMethod;

class DependencyCheck
{
    public function __construct(private readonly array $statusData, private array $processedStatus = [])
    {
        $this->processData();
    }

    public function processData(): void
    {
        if ($this->statusData['EXPERIAN'] !== true) {
            $this->processedStatus['EXPERIAN'] = false;
            $this->processedStatus[IdMethod::DrivingLicenseNumber->value] = false;
            $this->processedStatus[IdMethod::PassportNumber->value] = false;
            $this->processedStatus[IdMethod::NationalInsuranceNumber->value] = false;
            $this->processedStatus[IdMethod::PostOffice->value] = $this->statusData[IdMethod::PostOffice->value];
        } else {
            foreach ($this->statusData as $key => $value) {
                $this->processedStatus[$key] = $value;
            }
            if (
                $this->processedStatus[IdMethod::DrivingLicenseNumber->value] === false &&
                $this->processedStatus[IdMethod::PassportNumber->value] === false &&
                $this->processedStatus[IdMethod::NationalInsuranceNumber->value] === false
            ) {
                $this->processedStatus['EXPERIAN'] = false;
            }
        }
    }

    final public function getProcessedStatus(): array
    {
        return $this->processedStatus;
    }
}
