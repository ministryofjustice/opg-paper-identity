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

    /**
     * @psalm-return RTQRequest
     */
    public function buildRTQRequest(array $answersArray, CaseData $case): array
    {
        /** @var string $json */
        $json = $case->iiqControl;
        /** @var Control */
        $iiqControl = json_decode($json, true);

        $rtqConfig = [
            'Control' => [
                'URN' => $iiqControl['URN'],
                'AuthRefNo' => $iiqControl['AuthRefNo'],
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
