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

    public function __construct(
        protected array $services,
        protected CaseData $case,
        protected array $config
    ) {
        $this->mergeServices($services, $config);
    }

    private function mergeServices(
        array $services,
        array $config
    ): void {
        $configServices = array_merge(
            $config['opg_settings']['identity_documents'],
            $config['opg_settings']['identity_methods'],
            $config['opg_settings']['identity_services'],
        );

        $keys = array_keys($configServices);

        foreach ($keys as $key) {
            if (array_key_exists($key, $services)) {
                $this->availableServices[$key] = $services[$key];
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
            }
        }
        return $this->availableServices;
    }
}
