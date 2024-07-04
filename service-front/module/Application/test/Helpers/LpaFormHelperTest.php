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
    public function testFindLpa(
        string $caseUuid,
        array $responseData,
        Parameters $formData,
        FormInterface $form,
        array $siriusLpaResponse,
        array $opgCaseResponse,
    ): void {
        $lpaFormHelper = new LpaFormHelper();

        $processed = $lpaFormHelper->findLpa(
            $caseUuid,
            $formData,
            $form,
            $siriusLpaResponse,
            $opgCaseResponse,
        );

        $this->assertArrayHasKey('uuid', $processed->toArray());
        $this->assertArrayHasKey('form', $processed->toArray());
        $this->assertArrayHasKey('lpa_status', $processed->toArray());
        $this->assertArrayHasKey('message', $processed->toArray());
        $this->assertArrayHasKey('data', $processed->toArray());
        $this->assertArrayHasKey('additionalData', $processed->toArray());
        $this->assertEquals($caseUuid, $processed->getUuid());
        $this->assertEquals($form, $processed->getForm());
        $this->assertEquals($responseData['lpa_status'], $processed->getLpaStatus());
        $this->assertEquals($responseData['message'], $processed->getMessage());
        if (array_key_exists('data', $responseData)) {
            $this->assertEquals($responseData['data'], $processed->getData());
        }
        if (array_key_exists('additionalData', $responseData)) {
            $this->assertEquals($responseData['additionalData'], $processed->getData());
        }
    }


    public static function lpaData(): array
    {
        $slr = self::siriusLpaResponse();
        $olr = self::opgCaseResponse();

        $caseUuid = "9130a21e-6e5e-4a30-8b27-76d21b747e60";
        $goodLpa = "M-0000-0000-0000";
        $alreadyAddedLpa = "M-XYXY-YAGA-35G3";

        $notFoundLpa = "M-0000-0000-0002";
        $slrNotFound = self::nullSiriusLpaResponse($notFoundLpa);

        $alreadyDoneLpa = "M-0000-0000-0004";
        $slrComplete = $slr;
        $slrComplete['opg.poas.sirius']['uId'] = $alreadyDoneLpa;
        $slrComplete['opg.poas.lpastore']['status'] = 'complete';

        $draftLpa = "M-0000-0000-0005";
        $slrDraft = $slr;
        $slrDraft['opg.poas.sirius']['uId'] = $draftLpa;
        $slrDraft['opg.poas.lpastore']['status'] = 'draft';

        $onlineLpa = "M-0000-0000-0006";
        $slrOnline = $slr;
        $slrOnline['opg.poas.sirius']['uId'] = $onlineLpa;
        $slrOnline['opg.poas.lpastore']['certificateProvider']['channel'] = 'online';

        $noMatchLpa = "M-0000-0000-0006";
        $slrNoMatch = $slr;
        $slrNoMatch['opg.poas.sirius']['uId'] = $noMatchLpa;
        $slrNoMatch['opg.poas.lpastore']['status'] = 'no match';
        $slrNoMatch['opg.poas.lpastore']['certificateProvider']['address'] = [
            'line1' => '81 Penny Street',
            'line2' => 'Lancaster',
            'town' => 'Lancashire',
            'postcode' => 'LA1 2XN',
            'country' => 'United Kingdom',
        ];
        $slrNoMatch['opg.poas.lpastore']['certificateProvider']['firstNames'] = "Daniel";

        $mockResponseData = [
            "data" => [
                "case_uuid" => $caseUuid,
                "LPA_Number" => $goodLpa,
                "Type_Of_LPA" => "property-and-affairs",
                "Donor" => "Kitty Jenkins",
                "Status" => "processing",
                "CP_Name" => "David Smith",
                "CP_Address" => [
                    'line1' => '82 Penny Street',
                    'line2' => 'Lancaster',
                    'line3' => 'Lancashire',
                    'postcode' => 'LA1 1XN',
                    'country' => 'United Kingdom',
                ]
            ],
            "message" => "",
            "lpa_status" => "Success",
        ];

        $form = (new AttributeBuilder())->createForm(LpaReferenceNumber::class);
        $params = new Parameters(['lpa' => $mockResponseData['data']['LPA_Number']]);

        return [
            [
                $caseUuid,
                [
                    "data" => [
                        "case_uuid" => $caseUuid,
                        "LPA_Number" => $goodLpa,
                        "Type_Of_LPA" => "property-and-affairs",
                        "Donor" => "Kitty Jenkins",
                        "Status" => "processing",
                        "CP_Name" => "David Smith",
                        "CP_Address" => [
                            'line1' => '82 Penny Street',
                            'line2' => 'Lancaster',
                            'town' => 'Lancashire',
                            'postcode' => 'LA1 1XN',
                            'country' => 'United Kingdom',
                        ]
                    ],
                    "message" => "",
                    "lpa_status" => "Success",
                ],
                $params,
                $form,
                $slr,
                $olr,
            ],
            [
                $caseUuid,
                [
                    "message" => "This LPA has already been added to this ID check.",
                    "lpa_status" => "Already added"
                ],
                new Parameters(['lpa' => $alreadyAddedLpa]),
                $form,
                $slr,
                $olr,
            ],
            [
                $notFoundLpa,
                [
                    "message" => "No LPA found.",
                    "lpa_status" => 'Not Found',
                ],
                new Parameters(['lpa' => $notFoundLpa]),
                $form,
                $olr,
                $slrNotFound,
            ],
            [
                $caseUuid,
                [
                    "message" => "This LPA cannot be added as an ID check has already been completed for this LPA.",
                    "lpa_status" => "complete",
                ],
                new Parameters(['lpa' => $alreadyDoneLpa]),
                $form,
                $slrComplete,
                $olr,
            ],
            [
                $caseUuid,
                [
                    "message" => "This LPA cannot be added as it’s status is set to Draft.
                    LPAs need to be in the In Progress status to be added to this ID check.",
                    "lpa_status" => "draft",
                ],
                new Parameters(['lpa' => $draftLpa]),
                $form,
                $slrDraft,
                $olr,
            ],
            [
                $caseUuid,
                [
                    "message" => "This LPA cannot be added to this identity check because
                    the certificate provider has signed this LPA online.",
                    "lpa_status" => 'processing',
                    "data" => [
                        "case_uuid" => $caseUuid,
                        "LPA_Number" => $onlineLpa,
                        "Type_Of_LPA" => "property-and-affairs",
                        "Donor" => "Kitty Jenkins",
                        "Status" => "processing",
                        "CP_Name" => "David Smith",
                        "CP_Address" => [
                            'line1' => '82 Penny Street',
                            'line2' => 'Lancaster',
                            'town' => 'Lancashire',
                            'postcode' => 'LA1 1XN',
                            'country' => 'United Kingdom',
                        ]
                    ],
                ],
                new Parameters(['lpa' => $onlineLpa]),
                $form,
                $slrOnline,
                $olr,
            ],
            [
                $caseUuid,
                [
                    "message" => "This LPA cannot be added to this ID check because the" .
                        " certificate provider details on this LPA do not match. " .
                        "Edit the certificate provider record in Sirius if appropriate and find again.",
                    "lpa_status" => "no match",
                    "additional_data" => [
                        'name' => "Daniel Smith",
                        'address' => [
                            "line1" => "81 Penny Street",
                            "line2" => "Lancaster",
                            "town" => "Lancashire",
                            "postcode" => "LA1 2XN",
                            "country" => "United Kingdom"
                        ],
                        'name_match' => false,
                        'address_match' => false,
                        'error' => true,
                    ],
                    'data' => [
                        "case_uuid" => $caseUuid,
                        "LPA_Number" => $noMatchLpa,
                        "Type_Of_LPA" => 'property-and-affairs',
                        "Donor" => "Kitty Jenkins",
                        "Status" => 'no match',
                        "CP_Name" => "Daniel Smith",
                        "CP_Address" => [
                            'line1' => '81 Penny Street',
                            'line2' => 'Lancaster',
                            'town' => 'Lancashire',
                            'postcode' => 'LA1 2XN',
                            'country' => 'United Kingdom',
                        ]
                    ]
                ],
                new Parameters(['lpa' => $noMatchLpa]),
                $form,
                $slrNoMatch,
                $olr,
            ]
        ];
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
                        "line1" => "82 Penny Street",
                        "line2" => "Lancaster",
                        "town" => "Lancashire",
                        "postcode" => "LA1 1XN",
                        "country" => "United Kingdom",
                    ],
                    "channel" => "paper",
                    "email" => "Elton95@hotmail.com",
                    "firstNames" => "David",
                    "lastName" => "Smith",
                    "phone" => "cillum",
                    "uid" => "db71d0ce-d680-88c2-fa59-3c76b0b43864"
                ],
                "channel" => "paper",
                "donor" => [
                    "address" => [
                        "line1" => "7095 VonRueden Crossing",
                        "line2" => "Lancaster",
                        "town" => "Ann Arbor",
                        "postcode" => "PZ4 2SC",
                        "country" => "UM",
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
                "status" => "processing",
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

    private static function nullSiriusLpaResponse(string $lpa = null): array
    {
        $lpaRef = $lpa ?? "M-NC49-MV4M-E7P8";
        return [
            "type" => "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
            "title" => "Not Found",
            "status" => 404,
            "detail" => "Unable to load DigitalLpa with identifier: " . $lpaRef
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
                "line1" => "82 Penny Street",
                "line2" => "Lancaster",
                "town" => "Lancashire",
                "postcode" => "LA1 1XN",
                "country" => "United Kingdom",
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
}
