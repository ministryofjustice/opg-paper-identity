<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Helpers\LpaFormHelper;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;
use Application\Forms\LpaReferenceNumber;

class LpaFormHelperTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider lpaData
     */
//    public function testFindLpa(
//        string        $caseUuid,
//        string        $lpaNumber,
//        array         $responseData,
//        Parameters    $formData,
//        FormInterface $form,
//        array $siriusLpaResponse,
//        array $opgCaseResponse,
//        array         $templates = [],
//    ): void
//    {
////        $opgApiServiceMock = $this->createMock(OpgApiService::class);
//        $lpaFormHelper = new LpaFormHelper();
//
////        $lpaFormHelper
////            ->expects(self::once())
////            ->method('getDetailsData')
////            ->with($caseUuid)
////            ->willReturn($responseData);
//
//        $processed = $lpaFormHelper->findLpa(
//            $caseUuid,
//            $formData,
//            $form,
//            $siriusLpaResponse,
//            $opgCaseResponse,
//            $templates
//        );
//        $this->assertEquals($caseUuid, $processed->getUuid());
//        $this->assertEquals($templates['default'], $processed->getTemplate());
//        $this->assertArrayHasKey('lpa_response', $processed->getVariables());
//        $this->assertEquals($processed->getVariables()['lpa_response'], $responseData);
//    }


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
                self::siriusLpaResponse(),
                self::opgCaseResponse(),
                $templates
            ],
//            [
//                $caseUuid,
//                $alreadyAddedLpa,
//                [
//                    "uuid" => $caseUuid,
//                    "message" => "This LPA has already been added to this ID check.",
//                    "status" => 400,
//                    'data' => [
//                        "Status" => "Already added"
//                    ]
//                ],
//                new Parameters(['lpa' => $alreadyAddedLpa]),
//                $form,
//                $templates
//            ],
//            [
//                $caseUuid,
//                $notFoundLpa,
//                [
//                    "uuid" => $caseUuid,
//                    "message" => "No LPA found.",
//                    "status" => 400,
//                    'data' => [
//                        "Status" => "Not found"
//                    ]
//                ],
//                new Parameters(['lpa' => $notFoundLpa]),
//                $form,
//                $templates
//            ],
//            [
//                $caseUuid,
//                $alreadyDoneLpa,
//                [
//                    "uuid" => $caseUuid,
//                    "message" => "This LPA cannot be added as an ID check has already been completed for this LPA.",
//                    "status" => 400,
//                    'data' => [
//                        "Status" => "Already completed"
//                    ]
//                ],
//                new Parameters(['lpa' => $alreadyDoneLpa]),
//                $form,
//                $templates
//            ],
//            [
//                $caseUuid,
//                $draftLpa,
//                [
//                    "uuid" => $caseUuid,
//                    "message" => "This LPA cannot be added as it’s status is set to Draft.
//                    LPAs need to be in the In Progress status to be added to this ID check.",
//                    "status" => 400,
//                    'data' => [
//                        "Status" => "Draft"
//                    ]
//                ],
//                new Parameters(['lpa' => $draftLpa]),
//                $form,
//                $templates
//            ],
//            [
//                $caseUuid,
//                $onlineLpa,
//                [
//                    "uuid" => $caseUuid,
//                    "message" => "This LPA cannot be added to this identity check because the
//                    certificate provider has signed this LPA online.",
//                    "status" => 400,
//                    'data' => [
//                        "Status" => "Online"
//                    ]
//                ],
//                new Parameters(['lpa' => $onlineLpa]),
//                $form,
//                $templates
//            ]
        ];
    }

    /**
     * @dataProvider idCompareData
     */
    public function testIdCompare($siriusData, $opgData, $nameMatch, $addressMatch)
    {
//        $opgApiServiceMock = $this->createMock(OpgApiService::class);
        $lpaFormHelper = new LpaFormHelper();

        $result = $lpaFormHelper->compareCpRecords($opgData, $siriusData);

        $this->assertEquals($nameMatch, $result['name_match']);
        $this->assertEquals($addressMatch, $result['address_match']);
    }

    private static function siriusLpaResponse(): array
    {
        return [
            "opg.poas.lpastore" => [
                "attorneys" => [
                    [
                        "dateOfBirth" => "1908-02-14",
                        "status" => "active",
                        "channel" => "online",
                        "uid" => "8fed212b-38e4-644a-47ef-83e8e289eece",
                        "firstNames" => "Alejandrin",
                        "lastName" => "Collins",
                        "address" => [
                            "line1" => "166 Alisha Overpass",
                            "country" => "AO",
                            "town" => "Dubuque",
                            "line2" => "Jazmin Mission"
                        ],
                        "email" => "Jermey21@yahoo.com"
                    ],
                    [
                        "dateOfBirth" => "1916-08-26",
                        "status" => "replacement",
                        "channel" => "online",
                        "uid" => "d56dcbc6-15e4-202b-480e-cf144713ffd7",
                        "firstNames" => "Cruz",
                        "lastName" => "Hills",
                        "address" => [
                            "line1" => "943 Kaci Mountain",
                            "country" => "MK",
                            "line3" => "Salem",
                            "town" => "Country Club"
                        ],
                        "email" => "Tressa_Brown41@hotmail.com"
                    ],
                    [
                        "dateOfBirth" => "1900-05-21",
                        "status" => "removed",
                        "channel" => "paper",
                        "uid" => "c0674a52-6eb1-7655-5139-78998cca65aa",
                        "firstNames" => "Dudley",
                        "lastName" => "Pfeffer",
                        "address" => [
                            "line1" => "629 America Street",
                            "country" => "MN",
                            "line3" => "The Villages",
                            "line2" => "Shakira Roads"
                        ],
                        "email" => "Stella.Jakubowski51@gmail.com"
                    ]
                ],
                "certificateProvider" => [
                    "address" => [
                        "country" => "United Kingdom",
                        "line1" => "82 Penny Street",
                        "line2" => "Lancaster",
                        "line3" => "Lancashire",
                        "postcode" => "LA1 1XN"
                    ],
                    "channel" => "online",
                    "email" => "Elton95@hotmail.com",
                    "firstNames" => "David",
                    "lastName" => "Smith",
                    "phone" => "cillum",
                    "uid" => "db71d0ce-d680-88c2-fa59-3c76b0b43864"
                ],
                "channel" => "paper",
                "donor" => [
                    "address" => [
                        "country" => "UM",
                        "line1" => "7095 VonRueden Crossing",
                        "line2" => "Lancaster",
                        "postcode" => "PZ4 2SC",
                        "town" => "Ann Arbor"
                    ],
                    "dateOfBirth" => "1898-01-06",
                    "email" => "Ronny_Schultz73@gmail.com",
                    "firstNames" => "Esperanza",
                    "lastName" => "Walter",
                    "otherNamesKnownBy" => "Mrs. Laurie Schuppe",
                    "uid" => "07aff050-2700-66ae-c3ce-b96e4bc6b7d2"
                ],
                "howReplacementAttorneysMakeDecisionsDetails" => "enim eu",
                "howReplacementAttorneysStepIn" => "another-way",
                "lifeSustainingTreatmentOption" => "option-b",
                "lpaType" => "property-and-affairs",
                "peopleToNotify" => [
                    [
                        "uid" => "557a971e-cae3-25ea-151d-5fde6ec5fe21",
                        "firstNames" => "Eden",
                        "lastName" => "Kuhn",
                        "address" => [
                            "line1" => "56713 Archibald Unions",
                            "country" => "PL",
                            "line2" => "Golda Mews",
                            "line3" => "Thousand Oaks"
                        ]
                    ],
                    [
                        "uid" => "1cba68d2-3e93-8340-57a6-63aaf04f6e19",
                        "firstNames" => "Jaylan",
                        "lastName" => "Turcotte",
                        "address" => [
                            "line1" => "4705 Ebony Cape",
                            "country" => "MR",
                            "postcode" => "GH1 4FZ",
                            "line3" => "Nashua"
                        ]
                    ]
                ],
                "registrationDate" => "1918-08-28",
                "signedAt" => "1956-06-30T03:57:57.0Z",
                "status" => "Processing",
                "uid" => "M-804C-XHAD-59UQ",
                "updatedAt" => "1913-04-09T10:18:35.0Z",
                "whenTheLpaCanBeUsed" => "when-capacity-lost"
            ],
            "opg.poas.sirius" => [
                "donor" => [
                    "addressLine3" => "Moline",
                    "dob" => "1910-12-22",
                    "firstname" => "Kitty",
                    "postcode" => "JO2 5XI",
                    "surname" => "Jenkins",
                    "town" => "Janesville"
                ],
                "id" => 72757966,
                "uId" => "M-5P78-MEPH-8L4F"
            ]
        ];
    }

    private static function opgCaseResponse(): array
    {
        return [
            "id" => "b4d3a25f-d867-4d26-9213-f67ad3b68caf",
            "personType" => "cp",
            "firstName" => "David",
            "lastName" => "Smith",
            "dob" => "1999-01-01",
            "address" => [
                "82 Penny Street",
                "Lancaster",
                "Lancashire",
                "LA1 1XN"
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9"
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "selectedPostOfficeDeadline" => null,
            "selectedPostOffice" => null,
            "searchPostcode" => null,
            "idMethod" => null
        ];
    }

    public static function idCompareData(): array
    {
        $olr = self::opgCaseResponse();
        $slr = self::siriusLpaResponse();

        $olr['firstName'] = "John";
        $olr['lastName'] = "Doe";
        $slr['opg.poas.lpastore']['certificateProvider']['address'] = [
            'line1' => "", 'line2' => "", 'line3' => "", 'town' => "", 'postcode' => "", 'country' => ""
        ];

        return [
            [
                self::siriusLpaResponse(),
                self::opgCaseResponse(),
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

    /**
     * @dataProvider statusData
     */
    public function testCheckStatus($siriusData, $error, $message)
    {
        $lpaFormHelper = new LpaFormHelper();

        $result = $lpaFormHelper->checkStatus($siriusData);

        $this->assertEquals($error, $result['error']);
        $this->assertEquals($message, $result['message']);
    }

    public static function statusData(): array
    {
        $slr = self::siriusLpaResponse();

        $return[] = [
            $slr,
            false,
            ""
        ];

        $errors = [
            "nm" => [
                "status" => "No Match",
                "error" => "This LPA cannot be added to this ID check because the
                    certificate provider details on this LPA do not match.
                    Edit the certificate provider record in Sirius if appropriate and find again.",
                "additional_data" => ""
            ],
            "ac" => [
                "status" => "Already Complete",
                "error" => "This LPA cannot be added as an ID check has already been completed for this LPA."
            ],
            "dd" => [
                "status" => "Draft",
                "error" => "This LPA cannot be added as it’s status is set to Draft.
                    LPAs need to be in the In Progress status to be added to this ID check."
            ],
            "ol" => [
                "status" => "Started Online",
                "error" => "This LPA cannot be added to this identity check because
                    the certificate provider has signed this LPA online."
            ],
        ];

        foreach ($errors as $error) {
            $response = $slr;
            $response['opg.poas.lpastore']['status'] = $error['status'];
            $return[] = [
                $response,
                true,
                $error['error']
            ];
        }

        return $return;
    }
}
