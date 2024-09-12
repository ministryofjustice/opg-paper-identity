<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Model\Entity\CaseData;

class ConfigBuilder
{
    public function buildSAA(CaseData $case): array
    {
        $saaConfig = [];

        $saaConfig['Applicant'] = [
            'ApplicantIdentifier' => $case->id,
            'Name' => [
                'Title' => '',
                'Forename' => $case->firstName,
                'Surname' => $case->lastName,
            ]
        ];

        if (isset($case->dob)) {
            $saaConfig['Applicant']['DateOfBirth'] = [
                'CCYY' => date('Y', strtotime(date($case->dob))),
                'MM' => date('m', strtotime(date($case->dob))),
                'DD' => date('d', strtotime(date($case->dob))),
            ];
        }

        $saaConfig['ApplicationData'] = [
            'SearchConsent' => 'Y',
        ];
        $saaConfig['Control'] = [
                    'TestDatabase' => 'A',
        ];
        $saaConfig['LocationDetails'] = [
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
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function buildRTQ(array $answersArray): array
    {
        $rtqConfig = [];
        //@todo add in control stucture
        foreach ($answersArray as $answer) {
            $rtqConfig['Responses']['Response'] = [
                'QuestionID' => $answer['experianId'],
                'AnswerGiven' => $answer['answer'],
                'CustResponseFlag' => $answer['flag']
            ];
        }

        return $rtqConfig;
    }
}
