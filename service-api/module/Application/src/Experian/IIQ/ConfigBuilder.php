<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Model\Entity\CaseData;

class ConfigBuilder
{
    public function buildSAA(CaseData $case): array
    {
        $saaConfig = [];
        $saaConfig['sAARequest'] = [];
        $saaConfig['sAARequest']['Applicant'] = [
            'ApplicantIdentifier' => '1',
            'Name' => [
                'Title' => '',
                'Forename' => $case->firstName,
                'Surname' => $case->lastName,
            ]
        ];

        if (isset($case->dob)) {
            $saaConfig['sAARequest']['Applicant']['DateOfBirth'] = [
                'CCYY' => date('Y', strtotime(date($case->dob))),
                'MM' => date('m', strtotime(date($case->dob))),
                'DD' => date('d', strtotime(date($case->dob))),
            ];
        }

        $saaConfig['sAARequest']['ApplicationData'] = [
            'SearchConsent' => 'Y',
        ];
        $saaConfig['sAARequest']['Control'] = [
                    'TestDatabase' => 'A',
        ];
        $saaConfig['sAARequest']['LocationDetails'] = [
            'LocationIdentifier' => '1',
            'MultilineLocation' => [
                $case->address["line1"],
                $case->address["line2"],
                $case->address["line3"],
                $case->address["country"],
                $case->address["postcode"]
            ],
        ];
        return $saaConfig;
    }
}
