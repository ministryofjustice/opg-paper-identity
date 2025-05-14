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
        // these messages are being reviewed as part of ID-580
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
            in_array($case->caseProgress->fraudScore->decision ?? '', ['STOP', 'REFER'])
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
        /**
        * @psalm-suppress InvalidReturnStatement
        */
        return $bannerText;
    }

    public function processCase(CaseData $case): array
    {
        $docCheckResult = $case->caseProgress?->docCheck?->state ?? null;
        $fraudDecision = $case->caseProgress->fraudScore?->decision ?? null;
        $kbvsResult = $case->caseProgress?->kbvs?->result ?? null;
        $thinfile = $case->identityIQ?->thinfile ?? null;
        $restrictedMethods = $case->caseProgress->restrictedMethods ?? [];

        // vouching is only available for donors and only if they have not yet had a fraud-check, or passed one
        $this->availableRoutes[IdRoute::VOUCHING->value] = (
            $case->personType === 'donor' &&
            (is_null($fraudDecision) || in_array($fraudDecision, ['ACCEPT', 'CONTINUE', 'NODECISION']))
        );
        $this->availableRoutes[IdRoute::COURT_OF_PROTECTION->value] = ($case->personType === 'donor');

        if ($kbvsResult === true) {
            array_unshift($this->messages, $this->parseBannerText($case, self::LOCKED_COMPLETE));
            $this->availableRoutes = array_fill_keys(array_keys($this->availableRoutes), false);
        } elseif ($thinfile === true || $kbvsResult === false || $fraudDecision === 'NODECISION') {
            array_unshift($this->messages, $this->parseBannerText($case, self::LOCKED_EXPERIAN));
            $this->availableRoutes = array_merge($this->availableRoutes, self::KBV_FALSE);
        } elseif ($docCheckResult === false) {
            array_unshift($this->messages, $this->parseBannerText($case, self::LOCKED_ID_FAILURE));
            $this->availableRoutes = array_merge($this->availableRoutes, self::KBV_FALSE);
        } elseif ($docCheckResult === true) {
            array_unshift($this->messages, $this->parseBannerText($case, self::LOCKED_ID_SUCCESS));
            $this->availableRoutes = array_merge($this->availableRoutes, self::KBV_FALSE);
        } else {
            foreach ($restrictedMethods as $restrictedOption) {
                $this->availableRoutes[$restrictedOption] = false;
                $this->messages[] = sprintf(
                    $this->config['opg_settings']['banner_messages']['RESTRICTED_OPTIONS'],
                    $this->config['opg_settings']['identity_documents'][$restrictedOption]
                );
            }
        }

        return $this->toArray();
    }
}
