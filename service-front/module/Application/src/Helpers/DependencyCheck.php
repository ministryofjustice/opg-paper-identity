<?php

declare(strict_types=1);

namespace Application\Helpers;

use Aws\Ssm\SsmClient;
use Application\Enums\IdMethod;

class DependencyCheck
{
    private readonly array $statusData;

    private readonly array $processedStatus;

    public function __construct(array $statusData)
    {
        $this->statusData = $statusData;
        $this->processData();
    }

    public function getDependencyStatus(): array
    {
        return $this->statusData;
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
        }
    }

    public function getProcessedStatus(): array
    {
        return $this->processedStatus;
    }
}
