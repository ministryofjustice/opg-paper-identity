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
                'Forename' => $case->claimedIdentity?->firstName,
                'Surname' => $case->claimedIdentity?->lastName,
            ],
        ];

        if (! isset($case->claimedIdentity) || ! isset($case->claimedIdentity->dob)) {
            throw new RuntimeException('Cannot generate KBVs with date of birth');
        }

        $dob = DateTime::createFromFormat('Y-m-d', $case->claimedIdentity->dob);
        $saaConfig['Applicant']['DateOfBirth'] = [
            'CCYY' => $dob->format('Y'),
            'MM' => $dob->format('m'),
            'DD' => $dob->format('d'),
        ];

        if (! isset($case->caseProgress?->fraudScore)) {
            throw new RuntimeException('Fraudscore has not been set');
        }


        $decision = $case->caseProgress?->fraudScore?->decision;

        $saaConfig['ApplicationData'] = match ($decision) {
            'STOP', 'REFER' => [
                'SearchConsent' => 'Y',
                'Product' => '4 out of 4',
            ],
            'CONTINUE', 'ACCEPT' => [
                'SearchConsent' => 'Y',
            ],
            default =>  null
        };

        if (is_null($saaConfig['ApplicationData'])) {
            throw new RuntimeException('Fraudscore result is not recognised: ' . $case->fraudScore->decision);
        }

        if (! isset($case->claimedIdentity->address)) {
            throw new RuntimeException('Address has not been set');
        }
        $saaConfig['LocationDetails'] = [
            'LocationIdentifier' => '1',
            'UKLocation' => [
                'HouseName' => $case->claimedIdentity->address["line1"] ?? '',
                'Street' => $case->claimedIdentity->address["line2"] ?? '',
                'District' => $case->claimedIdentity->address["line3"] ?? '',
                'PostTown' => $case->claimedIdentity->address["town"] ?? '',
                'Postcode' => $case->claimedIdentity->address["postcode"] ?? '',
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
