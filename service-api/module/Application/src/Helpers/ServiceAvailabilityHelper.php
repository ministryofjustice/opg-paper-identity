<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Model\Entity\CaseData;
use Application\Fixtures\DataWriteHandler;
use Application\Sirius\EventSender;
use Psr\Log\LoggerInterface;

class ServiceAvailabilityHelper
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected array $availableServices;

    protected array $processedMessages = [];

    public function __construct(
        protected array $services,
        protected CaseData $case,
        protected array $config
    ) {
        $this->processGlobalServicesSettings();
        $this->mergeServices($this->config);
    }

    private function processGlobalServicesSettings(): void
    {
        $processedGlobalServices = [];

        if ($this->services['EXPERIAN'] !== true) {
            $processedGlobalServices['EXPERIAN'] = false;
            $processedGlobalServices['DRIVING_LICENCE'] = false;
            $processedGlobalServices['PASSPORT'] = false;
            $processedGlobalServices['NATIONAL_INSURANCE_NUMBER'] = false;
            $processedGlobalServices['POST_OFFICE'] = $this->services['POST_OFFICE'];
        } else {
            foreach ($this->services as $key => $value) {
                $processedGlobalServices[$key] = $value;
            }
        }

        if (
            $processedGlobalServices['DRIVING_LICENCE'] === false ||
            $processedGlobalServices['PASSPORT'] === false ||
            $processedGlobalServices['NATIONAL_INSURANCE_NUMBER'] === false
        ) {
            $this->processedMessages['service_status'] = "Some identity verification methods are not presently" .
                " available";
        }

        if (
            $processedGlobalServices['DRIVING_LICENCE'] === false &&
            $processedGlobalServices['PASSPORT'] === false &&
            $processedGlobalServices['NATIONAL_INSURANCE_NUMBER'] === false
        ) {
            $processedGlobalServices['EXPERIAN'] = false;
            $this->processedMessages['service_status'] = "Online identity verification is not presently available";
        }

        if (array_key_exists('message', $this->services)) {
            $this->processedMessages[] = $this->services['message'];
        }

        $this->services = $processedGlobalServices;
    }

    public function getProcessGlobalServicesSettings(): array
    {
        return $this->availableServices;
    }

    public function getProcessedMessage(): array
    {
        return $this->processedMessages;
    }

    final public function toArray(): array
    {
        return [
            'data' => $this->getProcessGlobalServicesSettings(),
            'messages' => $this->getProcessedMessage()
        ];
    }

    private function mergeServices(
        array $config
    ): void {
        $configServices = array_merge(
            $config['opg_settings']['identity_documents'],
            $config['opg_settings']['identity_methods'],
            $config['opg_settings']['identity_services'],
        );

        $keys = array_keys($configServices);

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->services)) {
                $this->availableServices[$key] = $this->services[$key];
            } else {
                $this->availableServices[$key] = true;
            }
        }
    }

    public function processServicesWithCaseData(): array
    {
        if (
            $this->case->fraudScore?->decision === 'STOP' ||
            $this->case->fraudScore?->decision === 'NODECISION' ||
            $this->case->identityCheckPassed === false
        ) {
            $this->availableServices['NATIONAL_INSURANCE_NUMBER'] = false;
            $this->availableServices['DRIVING_LICENCE'] = false;
            $this->availableServices['PASSPORT'] = false;

            if ($this->case->fraudScore?->decision === 'STOP') {
                $this->availableServices['VOUCHING'] = false;
                $this->processedMessages['banner'] =
                    $this->config['opg_settings']['banner_messages'][$this->case->personType]['STOP'];
            } else {
                $this->processedMessages['banner'] =
                    $this->config['opg_settings']['banner_messages'][$this->case->personType]['NODECISION'];
            }
        }

        return $this->toArray();
    }
}
