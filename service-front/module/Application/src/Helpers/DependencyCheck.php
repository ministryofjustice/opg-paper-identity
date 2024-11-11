<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\IdMethod;

class DependencyCheck
{
    protected array $processedStatus = [];

    protected string $processedMessage = '';

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
            $message = "Online identity verification is not presently available";
        }

        // override message with custom text if it comes back from the api
        if (array_key_exists('message', $this->statusData)) {
            $message = $this->statusData['message'];
        }

        $this->processedStatus = $processedData;
        $this->processedMessage = $message;
    }

    public function getProcessedStatus(): array
    {
        return $this->processedStatus;
    }

    public function getProcessedMessage(): string
    {
        return $this->processedMessage;
    }

    final public function toArray(): array
    {
        return [
            'data' => $this->processedStatus,
            'message' => $this->processedMessage
        ];
    }
}
