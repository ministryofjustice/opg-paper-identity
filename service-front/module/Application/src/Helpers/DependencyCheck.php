<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\IdMethod;

class DependencyCheck
{
    protected array $processedStatus = [];

    public function __construct(private readonly array $statusData)
    {
        $this->processData();
    }

    public function processData(): void
    {
        $message = "";
        $processedData = [];

        if ($this->statusData['EXPERIAN'] !== true) {
            $processedData['EXPERIAN'] = false;
            $processedData[IdMethod::DrivingLicenseNumber->value] = false;
            $processedData[IdMethod::PassportNumber->value] = false;
            $processedData[IdMethod::NationalInsuranceNumber->value] = false;
            $processedData[IdMethod::PostOffice->value] = $this->statusData[IdMethod::PostOffice->value];
        } else {
            foreach ($this->statusData as $key => $value) {
                $processedData[$key] = $value;
            }
        }

        if (
            $processedData[IdMethod::DrivingLicenseNumber->value] === false ||
            $processedData[IdMethod::PassportNumber->value] === false ||
            $processedData[IdMethod::NationalInsuranceNumber->value] === false
        ) {
            $message = "Some identity verification methods are not presently available";
        }

        if (
            $processedData[IdMethod::DrivingLicenseNumber->value] === false &&
            $processedData[IdMethod::PassportNumber->value] === false &&
            $processedData[IdMethod::NationalInsuranceNumber->value] === false
        ) {
            $processedData['EXPERIAN'] = false;
            $message = "Online identity verification it not presently available";
        }

        $this->processedStatus['data'] = $processedData;
        $this->processedStatus['message'] = $message;
    }

    final public function getProcessedStatus(): array
    {
        return $this->processedStatus;
    }
}
