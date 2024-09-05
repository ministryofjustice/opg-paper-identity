<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\ConfigBuilder;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\TestCase;

class ConfigBuilderTest extends TestCase
{
    private CaseData $caseMock;
    private ConfigBuilder $sut;

    public function setUp(): void
    {
        parent::setUp();

        $this->caseMock = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'firstName' => 'Maria',
            'lastName' => 'Williams',
            'personType' => 'donor',
            'dob' => '1960-01-01',
            'address' => [
                'line1' => '123 long street',
                'line2' => 'Kings Cross',
                'line3' => 'London',
                'postcode' => 'NW1 1SP',
                'country' => 'England'
            ],
        ]);
        // an instance of SUT
        $this->sut = new ConfigBuilder();
    }
    public function testSAAFormat(): void
    {
        $saaConfig = $this->sut->buildSAA($this->caseMock);

        $this->assertEquals($this->sAAFormatExpected(), $saaConfig);
    }

    public function sAAFormatExpected(): array
    {
        $saaConfig = [
            'sAARequest' => [
                'Applicant' => [
                    'ApplicantIdentifier' => '1',
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
                'Control' => [
                    'TestDatabase' => 'A',
                ],
                'LocationDetails' => [
                    'LocationIdentifier' => '1',
                    'MultilineLocation' => [
                        '123 long street',
                        'Kings Cross',
                        'London',
                        'England',
                        'NW1 1SP'
                    ],
                ],
            ],
        ];
        return $saaConfig;
    }
}
