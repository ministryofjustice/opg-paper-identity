<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Model\Entity\CaseData;
use DateTime;
use RuntimeException;

/**
 * @psalm-import-type Control from IIQService
 * @psalm-import-type SAARequest from IIQService
 * @psalm-import-type RTQRequest from IIQService
 */
class ConfigBuilder
{
    /**
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

        if (! isset($case->fraudScore)) {
            throw new RuntimeException('Fraudscore has not been set');
        }

        if (
            $case->fraudScore->decision === 'STOP' ||
            $case->fraudScore->decision === 'REFER'
        ) {
                $saaConfig['ApplicationData'] = [
                    'SearchConsent' => 'Y',
                    'Product' => '4 out of 4',
                ];
        } elseif (
            $case->fraudScore->decision === 'CONTINUE' ||
            $case->fraudScore->decision === 'ACCEPT'
        ) {
                $saaConfig['ApplicationData'] = [
                    'SearchConsent' => 'Y',
                    'Product' => '3 out of 4',
                ];
        } else {
            throw new RuntimeException('Fraudscore result is not recognised');
        }

        if (! isset($case->address)) {
            throw new RuntimeException('Address has not been set');
        }
        $saaConfig['LocationDetails'] = [
            'LocationIdentifier' => '1',
            'UKLocation' => [
                'HouseName' => $case->address["line1"] ?? '',
                'Street' => $case->address["line2"] ?? '',
                'District' => $case->address["line3"] ?? '',
                'PostTown' => $case->address["town"] ?? '',
                'Postcode' => $case->address["postcode"] ?? '',
            ],
        ];

        return $saaConfig;
    }

    /**
     * @psalm-return RTQRequest
     */
    public function buildRTQRequest(array $answersArray, CaseData $case): array
    {
        if ($case->iiqControl === null) {
            throw new RuntimeException('Cannot respond to questions without IIQ control data');
        }

        $rtqConfig = [
            'Control' => [
                'URN' => $case->iiqControl->urn,
                'AuthRefNo' => $case->iiqControl->authRefNo,
            ],
            'Responses' => [
                'Response' => [],
            ],
        ];

        foreach ($answersArray as $answer) {
            $rtqConfig['Responses']['Response'][] = [
                'QuestionID' => $answer['experianId'],
                'AnswerGiven' => $answer['answer'],
                'CustResponseFlag' => $answer['flag'],
                'AnswerActionFlag' => 'A',
            ];
        }

        return $rtqConfig;
    }
}
