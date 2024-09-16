<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Model\Entity\CaseData;
use DateTime;
use RuntimeException;

/**
 * @psalm-import-type SAARequest from IIQService
 */
class ConfigBuilder
{
    /**
     * @param CaseData $case
     * @psalm-return SAARequest
     */
    public function buildSAARequest(CaseData $case): array
    {
        $saaConfig = [];

        $saaConfig['Applicant'] = [
            'ApplicantIdentifier' => $case->id,
            'Name' => [
                'Title' => '',
                'Forename' => $case->firstName,
                'Surname' => $case->lastName,
            ],
        ];

        if (! isset($case->dob)) {
            throw new RuntimeException('Cannot generate KBVs with date of birth');
        }

        $dob = DateTime::createFromFormat('Y-m-d', $case->dob);
        $saaConfig['Applicant']['DateOfBirth'] = [
            'CCYY' => $dob->format('Y'),
            'MM' => $dob->format('m'),
            'DD' => $dob->format('d'),
        ];

        $saaConfig['ApplicationData'] = [
            'SearchConsent' => 'Y',
        ];
        $saaConfig['LocationDetails'] = [
            'LocationIdentifier' => '1',
            'UKLocation' => [
                'HouseName' => $case->address["line1"],
                'Street' => $case->address["line2"] ?? '',
                'District' => $case->address["line3"] ?? '',
                'PostTown' => $case->address["town"] ?? '',
                'Postcode' => $case->address["postcode"] ?? '',
            ],
        ];

        return $saaConfig;
    }
}