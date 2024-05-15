<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Forms\DrivingLicenceNumber;
use Application\Forms\LpaReferenceNumber;
use Application\Forms\NationalInsuranceNumber;
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
     * @dataProvider lpaData
     */
    public function testFindLpa(
        string $caseUuid,
        string $lpaNumber,
        array $responseData,
        Parameters $formData,
        FormInterface $form,
        array $templates = [],
    ): void {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorService = new FormProcessorHelper($opgApiServiceMock);

        $opgApiServiceMock
            ->expects(self::once())
            ->method('findLpa')
            ->with($caseUuid, $lpaNumber)
            ->willReturn($responseData);

        $processed = $formProcessorService->findLpa($caseUuid, $formData, $form, $templates);
        $this->assertEquals($responseData, $processed->getResponseData());
        $this->assertEquals($caseUuid, $processed->getUuid());
        $this->assertEquals($templates['default'], $processed->getTemplate());
        $this->assertArrayHasKey('lpa_response', $processed->getVariables());
        $this->assertEquals($processed->getVariables()['lpa_response'], $responseData);
    }


    public static function lpaData(): array
    {
        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $goodLpa = "M-0000-0000-0000";
        $alreadyAddedLpa = "M-0000-0000-0001";
        $notFoundLpa = "M-0000-0000-0002";
        $alreadyDoneLpa = "M-0000-0000-0004";
        $draftLpa = "M-0000-0000-0005";
        $onlineLpa = "M-0000-0000-0006";

        $mockResponseData = [
            "data" => [
                "case_uuid" => $caseUuid,
                "LPA_Number" => $goodLpa,
                "Type_Of_LPA" => "Personal welfare",
                "Donor" => "Mary Ann Chapman",
                "Status" => "Processing",
                "CP_Name" => "David Smith",
                "CP_Address" => [
                    "Line_1" => "1082 Penny Street",
                    "Line_2" => "Lancaster",
                    "Town" => "Lancashire",
                    "PostOfficePostcode" => "LA1 1XN",
                    "Country" => "United Kingdom"
                ]
            ],
            "message" => "Success",
            "status" => 200
        ];

        $form = (new AttributeBuilder())->createForm(LpaReferenceNumber::class);
        $params = new Parameters(['lpa' => $mockResponseData['data']['LPA_Number']]);
        $templates = [
            'default' => 'application/pages/cp/add_lpa',
        ];

        return [
            [
                $caseUuid,
                $goodLpa,
                $mockResponseData,
                $params,
                $form,
                $templates
            ],
            [
                $caseUuid,
                $alreadyAddedLpa,
                [
                    "uuid" => $caseUuid,
                    "message" => "This LPA has already been added to this ID check.",
                    "status" => 400,
                    'data' => [
                        "Status" => "Already added"
                    ]
                ],
                new Parameters(['lpa' => $alreadyAddedLpa]),
                $form,
                $templates
            ],
            [
                $caseUuid,
                $notFoundLpa,
                [
                    "uuid" => $caseUuid,
                    "message" => "No LPA found.",
                    "status" => 400,
                    'data' => [
                        "Status" => "Not found"
                    ]
                ],
                new Parameters(['lpa' => $notFoundLpa]),
                $form,
                $templates
            ],
            [
                $caseUuid,
                $alreadyDoneLpa,
                [
                    "uuid" => $caseUuid,
                    "message" => "This LPA cannot be added as an ID check has already been completed for this LPA.",
                    "status" => 400,
                    'data' => [
                        "Status" => "Already completed"
                    ]
                ],
                new Parameters(['lpa' => $alreadyDoneLpa]),
                $form,
                $templates
            ],
            [
                $caseUuid,
                $draftLpa,
                [
                    "uuid" => $caseUuid,
                    "message" => "This LPA cannot be added as it’s status is set to Draft. 
                    LPAs need to be in the In Progress status to be added to this ID check.",
                    "status" => 400,
                    'data' => [
                        "Status" => "Draft"
                    ]
                ],
                new Parameters(['lpa' => $draftLpa]),
                $form,
                $templates
            ],
            [
                $caseUuid,
                $onlineLpa,
                [
                    "uuid" => $caseUuid,
                    "message" => "This LPA cannot be added to this identity check because the 
                    certificate provider has signed this LPA online.",
                    "status" => 400,
                    'data' => [
                        "Status" => "Online"
                    ]
                ],
                new Parameters(['lpa' => $onlineLpa]),
                $form,
                $templates
            ]
        ];
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
        $formProcessorService = new FormProcessorHelper($opgApiServiceMock);

        if ($formData['inDate'] == 'yes') {
            $opgApiServiceMock
                ->expects(self::once())
                ->method('checkDlnValidity')
                ->with($formData->toArray()['dln'])
                ->willReturn($responseData['status']);
        }
        $processed = $formProcessorService->processDrivingLicenceForm($caseUuid, $formData, $form, $templates);

//        die (json_encode($processed->toArray()));

        $this->assertEquals($responseData, $processed->getResponseData());
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
        $formProcessorService = new FormProcessorHelper($opgApiServiceMock);

        if ($template !== 'default') {
            $opgApiServiceMock
                ->expects(self::once())
                ->method('checkNinoValidity')
                ->with($formData->toArray()['nino'])
                ->willReturn($responseData['status']);
        }

        $processed = $formProcessorService->processNationalInsuranceNumberForm($caseUuid, $formData, $form, $templates);

        $this->assertEquals($responseData, $processed->getResponseData());
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
}
