<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Model\Entity\CaseData;

class RouteAvailabilityHelper
{
    public const LOCKED_EXPERIAN = 'LOCKED_EXPERIAN';
    public const LOCKED_ID_FAILURE = 'LOCKED_ID_FAILURE';
    public const LOCKED_ID_SUCCESS = 'LOCKED_ID_SUCCESS';
    public const LOCKED_COMPLETE = 'LOCKED_COMPLETE';
    public const KBV_FALSE = [
        DocumentType::NationalInsuranceNumber->value => false,
        DocumentType::DrivingLicence->value => false,
        DocumentType::Passport->value => false,
        IdRoute::KBV->value => false,
    ];
    protected array $availableRoutes = [];
    protected array $messages = [];

    public function __construct(
        array $externalServices,
        protected array $config
    ) {
        $this->initAvailableRoutes();
        $this->processExternalServices($externalServices);
    }

    private function initAvailableRoutes(): void
    {
        $routes = array_merge(
            array_keys($this->config['opg_settings']['identity_documents']),
            array_keys($this->config['opg_settings']['identity_routes']),
        );
        $this->availableRoutes = array_fill_keys($routes, false);
    }

    private function processExternalServices(array $externalServices): void
    {
        // TODO: are the messages we set here correct?
        if (($externalServices[IdRoute::KBV->value] ?? false) === true) {
            $this->availableRoutes = array_merge($this->availableRoutes, $externalServices);
            if (
                ! $externalServices[DocumentType::NationalInsuranceNumber->value] ||
                ! $externalServices[DocumentType::Passport->value] ||
                ! $externalServices[DocumentType::DrivingLicence->value]
            ) {
                $this->messages[] = 'Some identity verification methods are not presently available';
            }
        } else {
            $this->availableRoutes = array_merge($this->availableRoutes, $externalServices, self::KBV_FALSE);
            $this->messages[] = 'Online identity verification is not presently available';
        }
    }

    public function toArray(): array
    {
        return [
            'data' => $this->availableRoutes,
            'messages' => $this->messages,
        ];
    }

    private function parseBannerText(CaseData $case, string $configMessage): string
    {

        if (
            $configMessage === self::LOCKED_EXPERIAN &&
            $case->personType === 'donor' &&
            in_array($case->caseProgress->fraudScore->decision, ["STOP", "REFER"])
        ) {
            $bannerText = $this->config['opg_settings']['banner_messages']['DONOR_VOUCH_UNAVAILABLE'];
        } else {
            $labels = $this->config['opg_settings']['person_type_labels'];
            $bannerText = str_replace(
                "%s",
                $labels[$case->personType],
                $this->config['opg_settings']['banner_messages'][$configMessage]
            );
        }
        return $bannerText;
    }

    public function processCase(CaseData $case): array
    {
        // vouching is only available for donors and only if they have not yet had a fraud-check, or passed one
        $this->availableRoutes[IdRoute::VOUCHING->value] = (
            $case->personType === 'donor' &&
            (
                is_null($case->caseProgress) ||
                is_null($case->caseProgress->fraudScore) ||
                in_array($case->caseProgress->fraudScore->decision, ["ACCEPT", "CONTINUE", "NODECISION"])
            )
        );
        $this->availableRoutes[IdRoute::COURT_OF_PROTECTION->value] = ($case->personType === 'donor');

        if ($case->caseProgress?->kbvs?->result === true) {
            array_unshift($this->messages, $this->parseBannerText($case, self::LOCKED_COMPLETE));
            $this->availableRoutes = array_fill_keys(array_keys($this->availableRoutes), false);
            return $this->toArray();
        }

        if ($case->identityIQ?->thinfile === true || $case->caseProgress?->kbvs?->result === false) {
            array_unshift($this->messages, $this->parseBannerText($case, self::LOCKED_EXPERIAN));
            $this->availableRoutes = array_merge($this->availableRoutes, self::KBV_FALSE);
            return $this->toArray();
        }

        if ($case->caseProgress?->docCheck?->state === false) {
            array_unshift($this->messages, $this->parseBannerText($case, self::LOCKED_ID_FAILURE));
            $this->availableRoutes = array_merge($this->availableRoutes, self::KBV_FALSE);
            return $this->toArray();
        }

        if ($case->caseProgress?->docCheck?->state === true) {
            // TODO: is this actually the behaviour we want?
            array_unshift($this->messages, $this->parseBannerText($case, self::LOCKED_ID_SUCCESS));
            $this->availableRoutes = array_merge($this->availableRoutes, self::KBV_FALSE);
            return $this->toArray();
        }

        foreach ($case->caseProgress->restrictedMethods ?? [] as $restrictedOption) {
            $this->availableRoutes[$restrictedOption] = false;
            $this->messages[] = sprintf(
                $this->config['opg_settings']['banner_messages']['RESTRICTED_OPTIONS'],
                $this->config['opg_settings']['identity_documents'][$restrictedOption]
            );
        }

        return $this->toArray();
    }
}
