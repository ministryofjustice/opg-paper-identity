<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers;

use Application\Exceptions\LpaTypeException;
use Application\Helpers\LpaStatusTypeHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LpaStatusTypeHelperTest extends TestCase
{
    #[DataProvider('lpaStatusTypeHelperData')]
    public function testLpaTypeHelper(
        array $lpa,
        string $personType,
        string $expectedStatus,
        bool $expectedStartable,
        bool $throwsException
    ): void {
        if ($throwsException) {
            $this->expectException(LpaTypeException::class);
        }

        $lpaStatusTypeHelper = new LpaStatusTypeHelper($lpa, $personType);

        $this->assertEquals($expectedStatus, $lpaStatusTypeHelper->getStatus());
        $this->assertEquals($expectedStartable, $lpaStatusTypeHelper->isStartable());
    }

    public static function lpaStatusTypeHelperData(): array
    {
        $draftLpa = self::siriusLpaResponse();
        $emptyDraftLpa = self::siriusLpaResponse();
        unset($emptyDraftLpa["opg.poas.lpastore"]);

        $registeredLpa = self::siriusLpaResponse();
        $registeredLpa["opg.poas.lpastore"]['status'] = 'registered';

        $registeredLpa2 = self::siriusLpaResponse();
        $registeredLpa2["opg.poas.lpastore"]['donor']['identityCheck'] = true;

        $inProgressLpa = self::siriusLpaResponse();
        $inProgressLpa["opg.poas.lpastore"]['status'] = 'in-progress';

        $statutoryWaitingPeriodLpa = self::siriusLpaResponse();
        $statutoryWaitingPeriodLpa["opg.poas.lpastore"]['status'] = 'statutory-waiting-period';

        $doNotRegisterLpa = self::siriusLpaResponse();
        $doNotRegisterLpa["opg.poas.lpastore"]['status'] = 'do-not-register';

        $suspendedLpa = self::siriusLpaResponse();
        $suspendedLpa["opg.poas.lpastore"]['status'] = 'suspended';

        $expiredLpa = self::siriusLpaResponse();
        $expiredLpa["opg.poas.lpastore"]['status'] = 'expired';

        $cannotRegisterLpa = self::siriusLpaResponse();
        $cannotRegisterLpa["opg.poas.lpastore"]['status'] = 'cannot-register';

        $cancelledLpa = self::siriusLpaResponse();
        $cancelledLpa["opg.poas.lpastore"]['status'] = 'cancelled';

        $doRegisteredLpa = self::siriusLpaResponse();
        $doRegisteredLpa["opg.poas.lpastore"]['status'] = 'de-registered';

        $invalidLpa = self::siriusLpaResponse();
        $invalidLpa["opg.poas.lpastore"]['status'] = 'invalid-lpa-status';

        return [
            [
                $draftLpa,
                'donor',
                'draft',
                true,
                false,
            ],
            [
                $draftLpa,
                'invalidPersonType',
                'draft',
                false,
                true,
            ],
            [
                $emptyDraftLpa,
                'donor',
                'draft',
                true,
                false,
            ],
            [
                $draftLpa,
                'certificateProvider',
                'draft',
                false,
                false,
            ],
            [
                $draftLpa,
                'voucher',
                'draft',
                false,
                false,
            ],
            [
                $registeredLpa,
                'donor',
                'registered',
                false,
                false,
            ],
            [
                $registeredLpa2,
                'donor',
                'registered',
                false,
                false,
            ],
            [
                $inProgressLpa,
                'donor',
                'in-progress',
                true,
                false,
            ],
            [
                $statutoryWaitingPeriodLpa,
                'donor',
                'statutory-waiting-period',
                true,
                false,
            ],
            [
                $doNotRegisterLpa,
                'donor',
                'do-not-register',
                true,
                false,
            ],
            [
                $inProgressLpa,
                'certificateProvider',
                'in-progress',
                true,
                false,
            ],
            [
                $statutoryWaitingPeriodLpa,
                'certificateProvider',
                'statutory-waiting-period',
                true,
                false,
            ],
            [
                $doNotRegisterLpa,
                'certificateProvider',
                'do-not-register',
                true,
                false,
            ],
            [
                $inProgressLpa,
                'voucher',
                'in-progress',
                true,
                false,
            ],
            [
                $statutoryWaitingPeriodLpa,
                'voucher',
                'statutory-waiting-period',
                true,
                false,
            ],
            [
                $doNotRegisterLpa,
                'voucher',
                'do-not-register',
                true,
                false,
            ],

            [
                $suspendedLpa,
                'donor',
                'suspended',
                false,
                false,
            ],
            [
                $expiredLpa,
                'voucher',
                'expired',
                false,
                false,
            ],
            [
                $cannotRegisterLpa,
                'voucher',
                'cannot-register',
                false,
                false,
            ],
            [
                $cancelledLpa,
                'voucher',
                'cancelled',
                false,
                false,
            ],
            [
                $doRegisteredLpa,
                'voucher',
                'de-registered',
                false,
                false,
            ],
            [
                $invalidLpa,
                'donor',
                'invalid',
                false,
                true,
            ],
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
                            "line2" => "Jazmin Mission",
                        ],
                        "email" => "Jermey21@yahoo.com",
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
                            "town" => "Country Club",
                        ],
                        "email" => "Tressa_Brown41@hotmail.com",
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
                            "line2" => "Shakira Roads",
                        ],
                        "email" => "Stella.Jakubowski51@gmail.com",
                    ],
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
                    "uid" => "db71d0ce-d680-88c2-fa59-3c76b0b43864",
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
                    "uid" => "07aff050-2700-66ae-c3ce-b96e4bc6b7d2",
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
                            "line3" => "Thousand Oaks",
                        ],
                    ],
                    [
                        "uid" => "1cba68d2-3e93-8340-57a6-63aaf04f6e19",
                        "firstNames" => "Jaylan",
                        "lastName" => "Turcotte",
                        "address" => [
                            "line1" => "4705 Ebony Cape",
                            "country" => "MR",
                            "postcode" => "GH1 4FZ",
                            "line3" => "Nashua",
                        ],
                    ],
                ],
                "registrationDate" => "1918-08-28",
                "signedAt" => "1956-06-30T03:57:57.0Z",
                "status" => "draft",
                "uid" => "M-804C-XHAD-59UQ",
                "updatedAt" => "1913-04-09T10:18:35.0Z",
                "whenTheLpaCanBeUsed" => "when-capacity-lost",
            ],
            "opg.poas.sirius" => [
                "caseSubtype" => "property-and-affairs",
                "donor" => [
                    "addressLine3" => "Moline",
                    "dob" => "1910-12-22",
                    "firstname" => "Kitty",
                    "postcode" => "JO2 5XI",
                    "surname" => "Jenkins",
                    "town" => "Janesville",
                ],
                "id" => 72757966,
                "uId" => "M-5P78-MEPH-8L4F",
            ],
        ];
    }
}
