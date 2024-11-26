<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\ConfigBuilder;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\IIQControl;
use PHPUnit\Framework\TestCase;

class ConfigBuilderTest extends TestCase
{
    public function testSAAFormat(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'firstName' => 'Maria',
            'lastName' => 'Williams',
            'personType' => 'donor',
            'dob' => '1960-01-01',
            'address' => [
                'line1' => '123 long street',
                'line2' => 'Kings Cross',
                'town' => 'London',
                'postcode' => 'NW1 1SP',
            ],
            'fraudScore' => [
                "decision" => "ACCEPT",
                "score" => 265
            ]
        ]);

        $configBuilder = new ConfigBuilder();

        $saaConfig = $configBuilder->buildSAARequest($caseData);

        $this->assertEquals([
            'Applicant' => [
                'ApplicantIdentifier' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                'Name' => [
                    'Title' => '',
                    'Forename' => 'Maria',
                    'Surname' => 'Williams',
                ],
                'DateOfBirth' => [
                    'CCYY' => '1960',
                    'MM' => '01',
                    'DD' => '01',
                ],
            ],
            'ApplicationData' => [
                'SearchConsent' => 'Y',
            ],
            'LocationDetails' => [
                'LocationIdentifier' => '1',
                'UKLocation' => [
                    'HouseName' => '123 long street',
                    'Street' => 'Kings Cross',
                    'District' => '',
                    'PostTown' => 'London',
                    'Postcode' => 'NW1 1SP',
                ],
            ],
        ], $saaConfig);
    }

    public function testRTQFormat(): void
    {
        $configBuilder = new ConfigBuilder();

        $caseData = CaseData::fromArray([
            'iiqControl' => IIQControl::fromArray([
                'urn' => 'test UUID',
                'authRefNo' => 'abc',
            ]),
            'fraudScore' => [
                "decision" => "ACCEPT",
                "score" => 265
            ]
        ]);

        $rtqConfig = $configBuilder->buildRTQRequest([
            [
                'experianId' => 'QID21',
                'answer' => 'BASINGSTOKE',
                'flag' => 1,
            ],
        ], $caseData);

        $this->assertEquals([
            'Control' => [
                'URN' => 'test UUID',
                'AuthRefNo' => 'abc',
            ],
            'Responses' => [
                'Response' => [
                    [
                        'QuestionID' => 'QID21',
                        'AnswerGiven' => 'BASINGSTOKE',
                        'CustResponseFlag' => 1,
                        'AnswerActionFlag' => 'A',
                    ],
                ],
            ],
        ], $rtqConfig);
    }
}
