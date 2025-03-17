<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Model\Entity\CaseData;

class ServiceAvailabilityHelper
{
    public const DECISION_STOP = 'STOP';
    public const DECISION_NODECISION = 'NODECISION';
    public const LOCKED = 'LOCKED';
    public const LOCKED_SUCCESS = 'LOCKED_SUCCESS';
    public const LOCKED_ID_SUCCESS = 'LOCKED_ID_SUCCESS';
    protected array $availableServices = [];
    protected array $processedMessages = [];
    protected array $additionalMessages = [];

    public function __construct(
        protected array $services,
        protected CaseData $case,
        protected array $config
    ) {
        $this->processGlobalServicesSettings();
        $this->processAdditionalMessages();
        $this->mergeServices($this->config);
    }

    private function processGlobalServicesSettings(): void
    {
        $processedGlobalServices = [];

        if (($this->services['EXPERIAN'] ?? false) !== true) {
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
            $this->processedMessages['service_status'] =
                "Some identity verification methods are not presently available";
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
    /**
     * @return array<string, string>
     */
    public function getProcessGlobalServicesSettings(): array
    {
        return $this->availableServices;
    }

    /**
     * @return array<string, string>
     */
    public function getProcessedMessage(): array
    {
        return $this->processedMessages;
    }

    final public function toArray(): array
    {
        return [
            'data' => $this->getProcessGlobalServicesSettings(),
            'messages' => $this->getProcessedMessage(),
            'additional_restriction_messages' => $this->processAdditionalMessages()
        ];
    }

    private function mergeServices(
        array $config
    ): void {
        $configServices = array_merge(
            $config['opg_settings']['identity_documents'] ?? [],
            $config['opg_settings']['identity_methods'] ?? [],
            $config['opg_settings']['identity_services'] ?? [],
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

    private function setServiceFlags(bool $flag, array $options = []): void
    {
        $this->availableServices['NATIONAL_INSURANCE_NUMBER'] = $flag;
        $this->availableServices['DRIVING_LICENCE'] = $flag;
        $this->availableServices['PASSPORT'] = $flag;
        $this->availableServices['EXPERIAN'] = $flag;

        foreach ($options as $service) {
            $this->availableServices[$service] = $flag;
        }
    }

    private function parseBannerText(string $configMessage): string
    {
        if ($configMessage === 'STOP') {
            switch ($this->case->personType) {
                case 'donor':
                    $bannerText = $this->config['opg_settings']['banner_messages']['DONOR_STOP'];
                    break;
                case 'certificateProvider':
                    $bannerText = $this->config['opg_settings']['banner_messages']['CP_STOP'];
                    break;
                case 'voucher':
                    $bannerText = $this->config['opg_settings']['banner_messages']['VOUCHER_STOP'];
                    break;
            }
        } else {
            $labels = $this->config['opg_settings']['person_type_labels'];

            $bannerText = str_replace(
                "%s",
                $labels[$this->case->personType],
                $this->config['opg_settings']['banner_messages'][$configMessage]
            );
        }
        /** @psalm-suppress InvalidReturnStatement */
        return $bannerText;
    }

    public function processServicesWithCaseData(): array
    {
        if ($this->case->caseProgress?->kbvs?->result === true) {
            $this->processedMessages['banner'] =
                $this->parseBannerText(self::LOCKED_SUCCESS);

            $this->setServiceFlags(false, [
                'NATIONAL_INSURANCE_NUMBER',
                'DRIVING_LICENCE',
                'PASSPORT',
                'POST_OFFICE',
                'EXPERIAN',
                'VOUCHING',
                'COURT_OF_PROTECTION'
            ]);

            return $this->toArray();
        }

        if ($this->case->identityCheckPassed === false) {
            $this->processedMessages['banner'] =
                $this->parseBannerText(self::DECISION_STOP);

            $this->setServiceFlags(false);

            return $this->toArray();
        }

        if ($this->case->caseProgress?->docCheck?->state === false) {
            $this->processedMessages['banner'] =
                $this->parseBannerText(self::LOCKED);

            $this->setServiceFlags(false);

            return $this->toArray();
        }

        if ($this->case->caseProgress?->docCheck?->state === true) {
            $this->processedMessages['banner'] =
                $this->parseBannerText(self::LOCKED_ID_SUCCESS);

            $this->setServiceFlags(false);

            return $this->toArray();
        }

        if (
            $this->case->caseProgress?->fraudScore?->decision === self::DECISION_STOP ||
            $this->case->caseProgress?->fraudScore?->decision === self::DECISION_NODECISION
        ) {
            $this->setServiceFlags(false);

            if ($this->case->caseProgress?->fraudScore?->decision === 'STOP') {
                $this->availableServices['VOUCHING'] = false;
                $this->processedMessages['banner'] =
                    $this->parseBannerText(self::DECISION_STOP);
            } else {
                $this->processedMessages['banner'] =
                    $this->parseBannerText(self::DECISION_NODECISION);
            }
        }

        if (!empty($this->case?->caseProgress?->restrictedMethods)) {
            foreach ($this->case?->caseProgress?->restrictedMethods as $method) {
                $this->availableServices[$method] = false;
            }
        }

        return $this->toArray();
    }

    public function processAdditionalMessages(): array
    {
        $messages = [];

        $map = [
            'NATIONAL_INSURANCE_NUMBER' => 'National insurance number',
            'PASSPORT' => 'Passport',
            'DRIVING_LICENCE' => 'Driving licence'
        ];

        foreach ($this->case?->caseProgress?->restrictedMethods as $restrictedOption) {
            $messages[] = sprintf($this->config['opg_settings']['banner_messages']['RESTRICTED_OPTIONS'], $map[$restrictedOption]);
        }
        return $messages;
    }
}
