<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Forms\DrivingLicenceNumber;
use Application\Forms\LpaReferenceNumber;
use Application\Forms\NationalInsuranceNumber;
use Application\Forms\PassportNumber;
use Application\Forms\PassportDate;
use Application\Helpers\FormProcessorHelper;
use Application\Services\OpgApiService;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

class FormProcessorHelperTest extends TestCase
{
//    private OpgApiService|MockObject $opgApiService;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider dlnData
     */
    public function testProcessDrivingLicenceForm(
        string $caseUuid,
        array $responseData,
        Parameters $formData,
        FormInterface $form,
        array $templates,
        string $template,
    ): void {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        if ($formData['inDate'] == 'yes') {
            $opgApiServiceMock
                ->expects(self::once())
                ->method('checkDlnValidity')
                ->with($formData->toArray()['dln'])
                ->willReturn($responseData['status']);
        }
        $processed = $formProcessorHelper->processDrivingLicenceForm($caseUuid, $formData, $form, $templates);
        $this->assertEquals($caseUuid, $processed->getUuid());
        $this->assertEquals($templates[$template], $processed->getTemplate());
    }


    public static function dlnData(): array
    {
        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $goodDln = "CHAPM301534MA9AY";
        $badDln = "CHAPM301534MA9AY";
        $insufficientDln = "CHAPM301534MA9AX";
        $shortDln = "CHAPM301534MA9";

        $mockSuccessResponseData = ["status" => "PASS"];
        $mockFailResponseData = ["status" => "NO_MATCH"];
        $mockNotEnoughDetailsResponseData = ["status" => "NOT_ENOUGH_DETAILS"];
        $mockInvalidResponseData = ["status" => "INVALID_FORMAT"];

        $form = (new AttributeBuilder())->createForm(DrivingLicenceNumber::class);
        $templates = [
            'default' => 'application/pages/driving_licence_number',
            'success' => 'application/pages/driving_licence_success',
            'fail' => 'application/pages/driving_licence_fail',
        ];

        return [
            [
                $caseUuid,
                $mockInvalidResponseData,
                new Parameters(['dln' => $shortDln, 'inDate' => 'no']),
                $form,
                $templates,
                'default'
            ],
            [
                $caseUuid,
                $mockSuccessResponseData,
                new Parameters(['dln' => $goodDln, 'inDate' => 'yes']),
                $form,
                $templates,
                'success'
            ],
            [
                $caseUuid,
                $mockFailResponseData,
                new Parameters(['dln' => $badDln, 'inDate' => 'yes']),
                $form,
                $templates,
                'fail'
            ],
            [
                $caseUuid,
                $mockNotEnoughDetailsResponseData,
                new Parameters(['dln' => $insufficientDln, 'inDate' => 'yes']),
                $form,
                $templates,
                'fail'
            ],
        ];
    }

    /**
     * @dataProvider ninoData
     */
    public function testProcessNationalInsuranceNumberForm(
        string $caseUuid,
        array $responseData,
        Parameters $formData,
        FormInterface $form,
        array $templates,
        string $template,
    ): void {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        if ($template !== 'default') {
            $opgApiServiceMock
                ->expects(self::once())
                ->method('checkNinoValidity')
                ->with($formData->toArray()['nino'])
                ->willReturn($responseData['status']);
        }

        $processed = $formProcessorHelper->processNationalInsuranceNumberForm($caseUuid, $formData, $form, $templates);
        $this->assertEquals($caseUuid, $processed->getUuid());
        $this->assertEquals($templates[$template], $processed->getTemplate());
    }


    public static function ninoData(): array
    {
        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $goodNino = "AA 11 22 33 A";
        $badNino = "AA 11 22 33 B";
        $shortNino = "AA112233";
        $insufficientNino = "AA 11 22 33 C";

        $mockSuccessResponseData = ["status" => "PASS"];
        $mockFailResponseData = ["status" => "NO_MATCH"];
        $mockNotEnoughDetailsResponseData = ["status" => "NOT_ENOUGH_DETAILS"];
        $mockInvalidResponseData = ["status" => "INVALID_FORMAT"];

        $form = (new AttributeBuilder())->createForm(NationalInsuranceNumber::class);
        $templates = [
            'default' => 'application/pages/national_insurance_number',
            'success' => 'application/pages/national_insurance_success',
            'fail' => 'application/pages/national_insurance_fail',
        ];

        return [
            [
                $caseUuid,
                $mockInvalidResponseData,
                new Parameters(['nino' => $shortNino]),
                $form,
                $templates,
                'default'
            ],
            [
                $caseUuid,
                $mockSuccessResponseData,
                new Parameters(['nino' => $goodNino]),
                $form,
                $templates,
                'success'
            ],
            [
                $caseUuid,
                $mockFailResponseData,
                new Parameters(['nino' => $badNino]),
                $form,
                $templates,
                'fail'
            ],
            [
                $caseUuid,
                $mockNotEnoughDetailsResponseData,
                new Parameters(['nino' => $insufficientNino]),
                $form,
                $templates,
                'fail'
            ],
        ];
    }

    /**
     * @dataProvider passportData
     */
    public function testProcessPassportNumberForm(
        string $caseUuid,
        array $responseData,
        Parameters $formData,
        FormInterface $form,
        array $templates,
        string $template,
    ): void {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        if ($template !== 'default') {
            $opgApiServiceMock
                ->expects(self::once())
                ->method('checkPassportValidity')
                ->with($formData->toArray()['passport'])
                ->willReturn($responseData['status']);
        }

        $processed = $formProcessorHelper->processPassportForm($caseUuid, $formData, $form, $templates);
        $this->assertEquals($caseUuid, $processed->getUuid());
        $this->assertEquals($templates[$template], $processed->getTemplate());
    }


    public static function passportData(): array
    {
        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $goodNino = "123456787";
        $badNino = "123456788";
        $shortNino = "AA112233";
        $insufficientNino = "123456789";

        $mockSuccessResponseData = ["status" => "PASS"];
        $mockFailResponseData = ["status" => "NO_MATCH"];
        $mockNotEnoughDetailsResponseData = ["status" => "NOT_ENOUGH_DETAILS"];
        $mockInvalidResponseData = ["status" => "INVALID_FORMAT"];

        $form = (new AttributeBuilder())->createForm(PassportNumber::class);
        $templates = [
            'default' => 'application/pages/passport_number',
            'success' => 'application/pages/passport_number_success',
            'fail' => 'application/pages/passport_number_fail',
        ];

        return [
            [
                $caseUuid,
                $mockInvalidResponseData,
                new Parameters(['passport' => $shortNino, 'inDate' => 'yes']),
                $form,
                $templates,
                'default'
            ],
            [
                $caseUuid,
                $mockSuccessResponseData,
                new Parameters(['passport' => $goodNino, 'inDate' => 'yes']),
                $form,
                $templates,
                'success'
            ],
            [
                $caseUuid,
                $mockFailResponseData,
                new Parameters(['passport' => $badNino, 'inDate' => 'yes']),
                $form,
                $templates,
                'fail'
            ],
            [
                $caseUuid,
                $mockNotEnoughDetailsResponseData,
                new Parameters(['passport' => $insufficientNino, 'inDate' => 'yes']),
                $form,
                $templates,
                'fail'
            ],
        ];
    }

    /**
     * @dataProvider passportDateData
     */
    public function testProcessPassportDateForm(
        string $caseUuid,
        Parameters $formData,
        FormInterface $form,
        array $templates,
        string $template,
        bool $validDate,
    ): void {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        $processed = $formProcessorHelper->processPassportDateForm($caseUuid, $formData, $form, $templates);

        if ($validDate) {
            $this->assertTrue($processed->getVariables()['valid_date']);
        } else {
            $this->assertTrue($processed->getVariables()['invalid_date']);
        }

        $this->assertTrue($processed->getVariables()['details_open']);
        $this->assertEquals($caseUuid, $processed->getUuid());
        $this->assertEquals($templates[$template], $processed->getTemplate());
    }

    public static function passportDateData(): array
    {
        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";

        $currentDate = new \DateTime();
        $periodNPass = 4;
        $periodNFail = 6;

        $today = $currentDate->format('Y-m-d');
        $fourYearsAgo = $currentDate->sub(new \DateInterval("P{$periodNPass}Y"))->format('Y-m-d');
        $sixYearsAgo = $currentDate->sub(new \DateInterval("P{$periodNFail}Y"))->format('Y-m-d');

        $form = (new AttributeBuilder())->createForm(PassportDate::class);
        $templates = [
            'default' => 'application/pages/passport_number',
        ];

        return [
            [
                $caseUuid,
                new Parameters([
                    'passport_issued_year' => explode("-", $today)[0],
                    'passport_issued_month' => explode("-", $today)[1],
                    'passport_issued_day' => explode("-", $today)[2],
                ]),
                $form,
                $templates,
                'default',
                true
            ],
            [
                $caseUuid,
                new Parameters([
                    'passport_issued_year' => explode("-", $fourYearsAgo)[0],
                    'passport_issued_month' => explode("-", $fourYearsAgo)[1],
                    'passport_issued_day' => explode("-", $fourYearsAgo)[2],
                ]),
                $form,
                $templates,
                'default',
                true
            ],
            [
                $caseUuid,
                new Parameters([
                    'passport_issued_year' => explode("-", $sixYearsAgo)[0],
                    'passport_issued_month' => explode("-", $sixYearsAgo)[1],
                    'passport_issued_day' => explode("-", $sixYearsAgo)[2],
                ]),
                $form,
                $templates,
                'default',
                false
            ],
        ];
    }

    /**
     * @dataProvider dateData
     */
    public function testProcessDataForm(array $params, string $expected): void
    {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        $actual = $formProcessorHelper->processDataForm($params);

        $this->assertEquals($expected, $actual);
    }

    public static function dateData(): array
    {
        return [
            [
                [
                    'dob_year' => "2002",
                    'dob_month' => "04",
                    'dob_day' => "01"
                ],
                "2002-04-01"
            ],
            [
                [
                    'dob_year' => "02",
                    'dob_month' => "04",
                    'dob_day' => "01"
                ],
                "2002-04-01"
            ],
            [
                [
                    'dob_year' => "1986",
                    'dob_month' => "04",
                    'dob_day' => "01"
                ],
                "1986-04-01"
            ],
            [
                [
                    'dob_year' => "86",
                    'dob_month' => "04",
                    'dob_day' => "01"
                ],
                "1986-04-01"
            ],
        ];
    }
}
