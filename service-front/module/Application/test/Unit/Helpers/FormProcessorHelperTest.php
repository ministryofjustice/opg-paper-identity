<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers;

use Application\Exceptions\OpgApiException;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\NationalInsuranceNumber;
use Application\Forms\PassportDate;
use Application\Forms\PassportNumber;
use Application\Helpers\FormProcessorHelper;
use Application\Services\OpgApiService;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormProcessorHelperTest extends TestCase
{
    #[DataProvider('dlnData')]
    public function testProcessDrivingLicenceForm(
        string $caseUuid,
        array $responseData,
        Parameters $formData,
        FormInterface $form,
        array $templates,
    ): void {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        $form->setData($formData);

        if ($formData['inDate'] == 'yes') {
            $opgApiServiceMock
                ->expects(self::once())
                ->method('checkDlnValidity')
                ->with($formData->toArray()['dln'])
                ->willReturn($responseData['status']);
        }

        $processed = $formProcessorHelper->processDrivingLicenceForm($caseUuid, $form, $templates);
        $this->assertEquals($caseUuid, $processed->getUuid());
        if (array_key_exists('validity', $processed->getVariables())) {
            $this->assertEquals($responseData['status'], $processed->getVariables()['validity']);
        }
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
            'success' => 'application/pages/document_success',
            'fail' => 'application/pages/driving_licence_number_fail',
            'thin_file' => 'application/pages/thin_file_failure',
            'fraud' => 'application/pages/fraud_failure'
        ];

        return [
            [
                $caseUuid,
                $mockInvalidResponseData,
                new Parameters(['dln' => $shortDln, 'inDate' => 'no']),
                $form,
                $templates,
            ],
            [
                $caseUuid,
                $mockSuccessResponseData,
                new Parameters(['dln' => $goodDln, 'inDate' => 'yes']),
                $form,
                $templates,
            ],
            [
                $caseUuid,
                $mockFailResponseData,
                new Parameters(['dln' => $badDln, 'inDate' => 'yes']),
                $form,
                $templates,
            ],
            [
                $caseUuid,
                $mockNotEnoughDetailsResponseData,
                new Parameters(['dln' => $insufficientDln, 'inDate' => 'yes']),
                $form,
                $templates,
            ],
        ];
    }

    #[DataProvider('ninoData')]
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

        $form->setData($formData);

        if ($template !== 'default') {
            $opgApiServiceMock
                ->expects(self::once())
                ->method('checkNinoValidity')
                ->with($caseUuid, $formData->toArray()['nino'])
                ->willReturn($responseData['status']);
        }

        $processed = $formProcessorHelper->processNationalInsuranceNumberForm($caseUuid, $form, $templates);
        $this->assertEquals($caseUuid, $processed->getUuid());
        if (array_key_exists('validity', $processed->getVariables())) {
            $this->assertEquals($responseData['status'], $processed->getVariables()['validity']);
        }
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
                'default',
            ],
            [
                $caseUuid,
                $mockSuccessResponseData,
                new Parameters(['nino' => $goodNino]),
                $form,
                $templates,
                'success',
            ],
            [
                $caseUuid,
                $mockFailResponseData,
                new Parameters(['nino' => $badNino]),
                $form,
                $templates,
                'fail',
            ],
            [
                $caseUuid,
                $mockNotEnoughDetailsResponseData,
                new Parameters(['nino' => $insufficientNino]),
                $form,
                $templates,
                'fail',
            ],
        ];
    }

    #[DataProvider('passportData')]
    public function testProcessPassportNumberForm(
        string $caseUuid,
        ?bool $response,
        Parameters $formData,
        FormInterface $form,
        array $templates,
        string $template,
    ): void {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        $form->setData($formData);

        if ($template !== 'default') {
            $opgApiServiceMock
                ->expects(self::once())
                ->method('checkPassportValidity')
                ->with($caseUuid, $formData->toArray()['passport'])
                ->willReturn($response);
        }

        $processed = $formProcessorHelper->processPassportForm($caseUuid, $form, $templates);
        $this->assertEquals($caseUuid, $processed->getUuid());
        if (array_key_exists('validity', $processed->getVariables())) {
            $this->assertEquals($response, $processed->getVariables()['validity']);
        }
    }


    public static function passportData(): array
    {
        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $passport = '123456789';

        $form = (new AttributeBuilder())->createForm(PassportNumber::class);
        $templates = [
            'default' => 'application/pages/passport_number',
            'success' => 'application/pages/document_success',
            'fail' => 'application/pages/passport_number_fail',
        ];

        return [
            'invalid form' => [
                $caseUuid,
                null,
                new Parameters(['passport' => $passport, 'inDate' => 'no']),
                $form,
                $templates,
                'default',
            ],
            'successful passport' => [
                $caseUuid,
                true,
                new Parameters(['passport' => $passport, 'inDate' => 'yes']),
                $form,
                $templates,
                'success',
            ],
            'unsuccessful passport' => [
                $caseUuid,
                false,
                new Parameters(['passport' => $passport, 'inDate' => 'yes']),
                $form,
                $templates,
                'fail',
            ],
        ];
    }

    #[DataProvider('passportDateData')]
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
        $periodNPass = "P16M";
        $periodNFail = "P18M";

        $today = $currentDate->format('Y-m-d');
        $validPassortDate = $currentDate->sub(new \DateInterval($periodNPass))->format('Y-m-d');
        $invalidPassortDate = $currentDate->sub(new \DateInterval($periodNFail))->format('Y-m-d');

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
                    'passport_issued_year' => explode("-", $validPassortDate)[0],
                    'passport_issued_month' => explode("-", $validPassortDate)[1],
                    'passport_issued_day' => explode("-", $validPassortDate)[2],
                ]),
                $form,
                $templates,
                'default',
                true
            ],
            [
                $caseUuid,
                new Parameters([
                    'passport_issued_year' => explode("-", $invalidPassortDate)[0],
                    'passport_issued_month' => explode("-", $invalidPassortDate)[1],
                    'passport_issued_day' => explode("-", $invalidPassortDate)[2],
                ]),
                $form,
                $templates,
                'default',
                false
            ],
        ];
    }

    #[DataProvider('dateData')]
    public function testprocessDateForm(array $params, string $expected): void
    {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        $actual = $formProcessorHelper->processDateForm($params);

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
            [
                [
                    'dob_year' => "1986",
                    'dob_month' => "4",
                    'dob_day' => "1"
                ],
                "1986-04-01"
            ],
            [
                [
                    'dob_year' => "",
                    'dob_month' => "4",
                    'dob_day' => "1"
                ],
                ""
            ],
            [
                [
                    'dob_year' => "",
                    'dob_month' => "",
                    'dob_day' => ""
                ],
                ""
            ],
        ];
    }

    #[DataProvider('processTemplateData')]
    public function testProcessTemplate(
        bool $exception,
        array $templates,
        array $fraudCheck,
        string $expected
    ): void {
        if ($exception) {
            $this->expectException(OpgApiException::class);
        }
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        $actual = $formProcessorHelper->processTemplate($fraudCheck, $templates);

        $this->assertEquals($expected, $actual);
    }

    public static function processTemplateData(): array
    {
        $templates = [
            'default' => 'application/pages/national_insurance_number',
            'success' => 'application/pages/document_success',
            'fail' => 'application/pages/national_insurance_number_fail',
            'thin_file' => 'application/pages/thin_file_failure',
            'fraud' => 'application/pages/fraud_failure',
        ];

        return [
            [
                false,
                $templates,
                [
                    "decision" => "ACCEPT",
                    "score" => 95
                ],
                "application/pages/document_success"
            ],
            [
                false,
                $templates,
                [
                    "decision" => "CONTINUE",
                    "score" => 95
                ],
                "application/pages/document_success"
            ],
            [
                false,
                $templates,
                [
                    "decision" => "REFER",
                    "score" => 95
                ],
                "application/pages/document_success"
            ],
            [
                false,
                $templates,
                [
                    "decision" => "NODECISION",
                    "score" => 970
                ],
                "application/pages/thin_file_failure"
            ],
            [
                false,
                $templates,
                [
                    "decision" => "STOP",
                    "score" => 980
                ],
                "application/pages/document_success"
            ],
        ];
    }
}
