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
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        $opgApiServiceMock
            ->expects(self::once())
            ->method('findLpa')
            ->with($caseUuid, $lpaNumber)
            ->willReturn($responseData);

        $processed = $formProcessorHelper->findLpa(
            $caseUuid,
            $formData,
            $form,
            self::siriusLpaResponse(),
            $templates
        );
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
                    "Postcode" => "LA1 1XN",
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
     * @dataProvider idCompareData
     */
    public function testIdCompare($siriusData, $opgData, $nameMatch, $addressMatch)
    {
        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $formProcessorHelper = new FormProcessorHelper($opgApiServiceMock);

        $result = $formProcessorHelper->compareCpRecords($opgData, $siriusData);

        $this->assertEquals($nameMatch, $result['name_match']);
        $this->assertEquals($addressMatch, $result['address_match']);
    }

    private static function siriusLpaResponse(): array
    {
        return json_decode('{
          "opg.poas.lpastore": {
            "attorneys": [
              {
                "dateOfBirth": "1908-02-14",
                "status": "active",
                "channel": "online",
                "uid": "8fed212b-38e4-644a-47ef-83e8e289eece",
                "firstNames": "Alejandrin",
                "lastName": "Collins",
                "address": {
                  "line1": "166 Alisha Overpass",
                  "country": "AO",
                  "town": "Dubuque",
                  "line2": "Jazmin Mission"
                },
                "email": "Jermey21@yahoo.com"
              },
              {
                "dateOfBirth": "1916-08-26",
                "status": "replacement",
                "channel": "online",
                "uid": "d56dcbc6-15e4-202b-480e-cf144713ffd7",
                "firstNames": "Cruz",
                "lastName": "Hills",
                "address": {
                  "line1": "943 Kaci Mountain",
                  "country": "MK",
                  "line3": "Salem",
                  "town": "Country Club"
                },
                "email": "Tressa_Brown41@hotmail.com"
              },
              {
                "dateOfBirth": "1900-05-21",
                "status": "removed",
                "channel": "paper",
                "uid": "c0674a52-6eb1-7655-5139-78998cca65aa",
                "firstNames": "Dudley",
                "lastName": "Pfeffer",
                "address": {
                  "line1": "629 America Street",
                  "country": "MN",
                  "line3": "The Villages",
                  "line2": "Shakira Roads"
                },
                "email": "Stella.Jakubowski51@gmail.com"
              }
            ],
            "certificateProvider": {
              "address": {
                "country": "United Kingdom",
                "line1": "82 Penny Street",
                "line2": "Lancaster",
                "line3": "Lancashire",
                "postcode": "LA1 1XN"
              },
              "channel": "online",
              "email": "Elton95@hotmail.com",
              "firstNames": "David",
              "lastName": "Smith",
              "phone": "cillum",
              "uid": "db71d0ce-d680-88c2-fa59-3c76b0b43864"
            },
            "channel": "paper",
            "donor": {
              "address": {
                "country": "UM",
                "line1": "7095 VonRueden Crossing",
                "line2": "Lancaster",
                "postcode": "PZ4 2SC",
                "town": "Ann Arbor"
              },
              "dateOfBirth": "1898-01-06",
              "email": "Ronny_Schultz73@gmail.com",
              "firstNames": "Esperanza",
              "lastName": "Walter",
              "otherNamesKnownBy": "Mrs. Laurie Schuppe",
              "uid": "07aff050-2700-66ae-c3ce-b96e4bc6b7d2"
            },
            "howReplacementAttorneysMakeDecisionsDetails": "enim eu",
            "howReplacementAttorneysStepIn": "another-way",
            "lifeSustainingTreatmentOption": "option-b",
            "lpaType": "property-and-affairs",
            "peopleToNotify": [
              {
                "uid": "557a971e-cae3-25ea-151d-5fde6ec5fe21",
                "firstNames": "Eden",
                "lastName": "Kuhn",
                "address": {
                  "line1": "56713 Archibald Unions",
                  "country": "PL",
                  "line2": "Golda Mews",
                  "line3": "Thousand Oaks"
                }
              },
              {
                "uid": "1cba68d2-3e93-8340-57a6-63aaf04f6e19",
                "firstNames": "Jaylan",
                "lastName": "Turcotte",
                "address": {
                  "line1": "4705 Ebony Cape",
                  "country": "MR",
                  "postcode": "GH1 4FZ",
                  "line3": "Nashua"
                }
              }
            ],
            "registrationDate": "1918-08-28",
            "signedAt": "1956-06-30T03:57:57.0Z",
            "status": "registered",
            "uid": "M-804C-XHAD-59UQ",
            "updatedAt": "1913-04-09T10:18:35.0Z",
            "whenTheLpaCanBeUsed": "when-capacity-lost"
          },
          "opg.poas.sirius": {
            "donor": {
              "addressLine3": "Moline",
              "dob": "1910-12-22",
              "firstname": "Kitty",
              "postcode": "JO2 5XI",
              "surname": "Jenkins",
              "town": "Janesville"
            },
            "id": 72757966,
            "uId": "M-5P78-MEPH-8L4F"
          }
        }', true);
    }

    private static function opgLpaResponse(): array
    {
        return json_decode('{
          "data": {
            "case_uuid": "284afff7-cf11-4b2d-a3fb-00bf98322f37",
            "LPA_Number": "M-0000-0000-0000",
            "Type_Of_LPA": "Personal welfare",
            "Donor": "Mary Ann Chapman",
            "Status": "Processing",
            "CP_Name": "David Smith",
            "CP_Address": {
              "Line_1": "82 Penny Street",
              "Line_2": "Lancaster",
              "Town": "Lancashire",
              "Postcode": "LA1 1XN",
              "Country": "United Kingdom"
            }
          },
          "message": "Success",
          "status": 200,
          "uuid": "284afff7-cf11-4b2d-a3fb-00bf98322f37"
        }', true);
    }

    public static function idCompareData(): array
    {
        $olr = self::opgLpaResponse();
        $slr = self::siriusLpaResponse();

        $olr['data']['CP_Name'] = "John Doe";
        $slr['opg.poas.lpastore']['certificateProvider']['address'] = [
            'line1' => "", 'line2' => "", 'line3' => "", 'town' => "", 'postcode' => "", 'country' => ""
        ];

        return [
            [
                self::siriusLpaResponse(),
                self::opgLpaResponse(),
                true,
                true
            ],
            [
                $slr,
                $olr,
                false,
                false
            ]
        ];
    }
}
