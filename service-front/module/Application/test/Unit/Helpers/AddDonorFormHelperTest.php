<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers;

use Application\Enums\LpaActorTypes;
use Application\Enums\LpaStatusType;
use Application\Helpers\AddDonorFormHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddDonorFormHelperTest extends TestCase
{
    private VoucherMatchLpaActorHelper&MockObject $matchHelperMock;
    private AddDonorFormHelper $addDonorFormHelper;

    public static string $firstName = 'Joe';
    public static string $lastName = 'Blogs';
    public static string $fullName = 'Joe Blogs';
    public static string $dobSiriusFmt = '10/02/1990';
    public static string $dobNormalFmt = '1990-02-10';
    public static string $dobLongFmt = '10 Feb 1990';
    public static array $address = [
        'line1' => '123 Fakestreet',
        'line2' => '',
        'line3' => '',
        'town' => 'faketown',
        'postcode' => 'FA2 3KE',
        'country' => ''
    ];

    public static string $cpMatchMessage = 'There is a certificate provider called Joe Blogs named on this LPA. ' .
        'A certificate provider cannot vouch for the identity of a donor. ' .
        'Confirm that these are two different people with the same name.';

    public static function getLpa(array $additionalKeys = []): array
    {
        $lpa = [
            'opg.poas.sirius' => [
                'donor' => [
                    'firstname' => self::$firstName,
                    'surname' => self::$lastName,
                    'dob' => self::$dobSiriusFmt,
                    'addressLine1' => self::$address['line1'],
                    'town' => self::$address['town'],
                    'postcode' => self::$address['postcode'],
                ]
            ]
        ];
        return array_merge_recursive($lpa, $additionalKeys);
    }

    public static function getDetailsData(): array
    {
        return [
            'firstName' => self::$firstName,
            'lastName' => self::$lastName,
            'dob' => self::$dobNormalFmt,
            'address' => self::$address,
            'lpas' => ['M-0000-0000-0000'],
        ];
    }

    public static function getAdditionalRows(string $type): array
    {
        if ($type === 'CP') {
            return [
                [
                    'type' => 'Certificate provider name',
                    'value' => self::$fullName,
                ]
            ];
        } elseif ($type === 'ATTORNEY') {
            return [
                [
                    'type' => 'Attorney name',
                    'value' => self::$fullName,
                ],
                [
                    'type' => 'Attorney date of birth',
                    'value' => self::$dobLongFmt,
                ]
            ];
        }
        return [];
    }

    public function setUp(): void
    {
        $this->matchHelperMock = $this->createMock(VoucherMatchLpaActorHelper::class);

        parent::setUp();

        $this->addDonorFormHelper = new AddDonorFormHelper($this->matchHelperMock);
    }

    public function testGetDonorNameFromSiriusResponse(): void
    {
        $response = $this->addDonorFormHelper->getDonorNameFromSiriusResponse(self::getLpa());
        $this->assertEquals(self::$fullName, $response);
    }

    public function testGetDonorDobFromSiriusResponse(): void
    {
        $response = $this->addDonorFormHelper->getDonorDobFromSiriusResponse(self::getLpa());
        $this->assertEquals(self::$dobLongFmt, $response);
    }

    #[DataProvider('statusData')]
    public function testCheckLpaStatus(?array $lpaStoreData, array $expectedResult): void
    {
        $lpaData = self::getLpa([
            'opg.poas.lpastore' => $lpaStoreData
        ]);
        $response = $this->addDonorFormHelper->checkLpaStatus($lpaData);
        $this->assertEquals($expectedResult, $response);
    }

    public static function statusData(): array
    {
        return [
            [
                null,
                ['problem' => true, 'message' => 'No LPA Found.']
            ],
            [
                [],
                ['problem' => true, 'message' => 'No LPA Found.']
            ],
            [
                ['status' => 'registered'],
                [
                    'problem' => true,
                    'message' => 'This LPA cannot be added as an ID check has already been completed for this LPA.'
                ]
            ],
            [
                ['status' => 'draft'],
                [
                    'problem' => true,
                    'message' => "This LPA cannot be added as it’s status is set to \"Draft\". " .
                        "LPAs need to be in the \"In progress\" status to be added to this ID check."
                    ]
                ],
            [
                ['status' => 'in-progress'],
                ['problem' => false, 'message' => '']
            ]
        ];
    }

    #[DataProvider('idMatchData')]
    public function testCheckLpaIdMatch(
        array|bool $checkMatchReturn,
        mixed $checkAddressReturn,
        mixed $compareNameReturn,
        ?array $lpastore,
        array $expectedResponse
    ): void {
        $this
            ->matchHelperMock
            ->expects(self::once())
            ->method('checkMatch')
            ->willReturn($checkMatchReturn);

        if (! is_null($checkAddressReturn)) {
            $this
                ->matchHelperMock
                ->expects(self::once())
                ->method('checkAddressDonorMatch')
                ->willReturn($checkAddressReturn);
        }

        if (! is_null($compareNameReturn)) {
            $this
                ->matchHelperMock
                ->expects(self::once())
                ->method('compareName')
                ->willReturn($compareNameReturn);
        }

        $lpa = self::getLpa(['opg.poas.lpastore' => $lpastore]);
        $response = $this->addDonorFormHelper->checkLpaIdMatch($lpa, self::getDetailsData());
        $this->assertEquals($expectedResponse, $response);
    }

    public static function idMatchData(): array
    {
        $emptyResponse = [
            "problem" => false,
            "error" => false,
            "warning" => "",
            "message" => "",
            "additionalRows" => [],
        ];

        $baseMatch = [
            'firstName' => self::$firstName,
            'lastName' => self::$lastName,
            'dob' => self::$dobNormalFmt,
            'type' => '',
        ];

        $nameDobMatchDonor = array_merge($baseMatch, ['type' => LpaActorTypes::DONOR->value]);
        $nameDobMatchAttorney = array_merge($baseMatch, ['type' => LpaActorTypes::ATTORNEY->value]);

        $donorMatchResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot have the same name and date of birth as the donor.',
            'warning' => 'donor-match'
        ]);

        $attorneyMatchResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot have the same name and date of birth as an attorney.',
            'warning' => 'actor-match',
            'additionalRows' => self::getAdditionalRows('ATTORNEY')
        ]);

        $addressMatchResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot live at the same address as the donor.',
            'warning' => 'address-match'
        ]);

        $nameMatchCpLpaStore = [
            'certificateProvider' => [
                'firstNames' => self::$firstName,
                'lastName' => self::$lastName,
            ]
            ];

        $nameMatchCpResponse = array_merge($emptyResponse, [
            'message' => self::$cpMatchMessage,
            'warning' => 'actor-match',
            'additionalRows' => self::getAdditionalRows('CP'),
        ]);

        return [
            // no matches
            [false, false, false, null, $emptyResponse],
            // match on name and dob with DONOR
            [$nameDobMatchDonor, null, null, null, $donorMatchResponse],
            // match on name and dob with ATTORNEY
            [$nameDobMatchAttorney, null, null, null, $attorneyMatchResponse],
            // match on address with DONOR
            [false, true, null, null, $addressMatchResponse],
            // match on name with CP
            [false, false, true, $nameMatchCpLpaStore, $nameMatchCpResponse],
        ];
    }
    /**
     * @psalm-suppress NamedArgumentNotAllowed
     */
    #[DataProvider('processLpasData')]
    public function testProcessLpas(
        array $lpasData,
        ?array $checkLpaStatusReturns,
        ?array $checkLpaIdMatchReturns,
        array $expectedResponse
    ): void {
        $helper = $this->getMockBuilder(AddDonorFormHelper::class)
            ->setConstructorArgs([new VoucherMatchLpaActorHelper()])
            ->onlyMethods(['checkLpaStatus', 'checkLpaIdMatch'])
            ->getMock();

        if (! is_null($checkLpaStatusReturns)) {
            $helper
                ->expects($this->exactly(count($checkLpaStatusReturns)))
                ->method('checkLpaStatus')
                ->willReturnOnConsecutiveCalls(...$checkLpaStatusReturns);
        }

        if (! is_null($checkLpaIdMatchReturns)) {
            $helper
                ->expects($this->exactly(count($checkLpaIdMatchReturns)))
                ->method('checkLpaIdMatch')
                ->willReturnOnConsecutiveCalls(...$checkLpaIdMatchReturns);
        }

        $response = $helper->processLpas($lpasData, self::getDetailsData());
        $this->assertEquals($expectedResponse, $response);
    }

    public static function processLpasData(): array
    {
        $baseResponse = [
            'lpasCount' => 0,
            'problem' => false,
            'error' => false,
            'warning' => '',
            'message' => '',
            'additionalRows' => [],
        ];

        $baseResponseDonorInfo = array_merge($baseResponse, [
            'donorName' => self::$fullName,
            'donorDob' => self::$dobLongFmt,
            'donorAddress' => self::$address,
        ]);

        $baseCheckLpaStatusResponse = [
            'problem' => false,
            'message' => ""
        ];

        $baseCheckLpaIdMatchResponse = [
            "problem" => false,
            "error" => false,
            "warning" => "",
            "message" => "",
            "additionalRows" => [],
        ];

        $problemStatusResponse = [
            'problem' => true,
            'message' => 'problem message',
        ];

        $errorIdCheckResponse = [
            'problem' => false,
            'error' => true,
            'warning' => 'actor-match',
            'message' => 'The person vouching cannot have the same name and date of birth as an attorney.',
            'additionalRows' => self::getAdditionalRows('ATTORNEY')
        ];

        $warningIdCheckResponse = array_merge($baseCheckLpaIdMatchResponse, [
            'warning' => 'actor-match',
            'message' => self::$cpMatchMessage,
            'additionalRows' => self::getAdditionalRows('CP'),
        ]);

        $singleProblemResponse = array_merge($baseResponse, $problemStatusResponse);

        $multipleProblemResponse = array_merge($baseResponse, [
            'problem' => true,
            'message' => "These LPAs cannot be added as they do not have the correct status for an ID check. " .
                "LPAs need to be in the \"In progress\" status to be added to this identity check.",
        ]);

        $lpaOne = self::getLpa([
            'opg.poas.sirius' => [
                'uId' => 'M-1111-1111-1111',
                'caseSubtype' => 'personal-welfare'
            ]
        ]);

        $lpaOneResponse = array_merge($baseCheckLpaIdMatchResponse, [
            'uId' => 'M-1111-1111-1111',
            'type' => 'PW'
        ]);

        $lpaTwo = self::getLpa([
            'opg.poas.sirius' => [
                'uId' => 'M-2222-2222-2222',
                'caseSubtype' => 'property-and-affairs'
            ]
        ]);

        $lpaTwoResponse = array_merge($baseCheckLpaIdMatchResponse, [
            'uId' => 'M-2222-2222-2222',
            'type' => 'PA'
        ]);

        return [
            // no LPAs
            [
                [],
                null,
                null,
                array_merge($baseResponse, [
                    'problem' => true,
                    'message' => 'No LPA Found.'
                ])
            ],
            // lpa already on Id Check
            [
                [self::getLpa(['opg.poas.sirius' => ['uId' => 'M-0000-0000-0000']])],
                null,
                null,
                array_merge($baseResponse, [
                    'problem' => true,
                    'message' => 'This LPA has already been added to this identity check.'
                ])
            ],
            // single problem LPA
            [
                [$lpaOne],
                [$problemStatusResponse],
                null,
                $singleProblemResponse
            ],
            // multiple problem LPAs
            [
                [$lpaOne, $lpaTwo],
                [$problemStatusResponse, $problemStatusResponse],
                null,
                $multipleProblemResponse,
            ],
            // one problem, one happy LPA
            [
                [$lpaOne, $lpaTwo],
                [$problemStatusResponse, $baseCheckLpaStatusResponse],
                [$baseCheckLpaIdMatchResponse],
                array_merge($baseResponseDonorInfo, [
                    'lpasCount' => 1,
                    'lpas' => [ 1 => $lpaTwoResponse]
                ])
            ],
            // one error
            [
                [$lpaOne],
                [$baseCheckLpaStatusResponse],
                [$errorIdCheckResponse],
                array_merge($baseResponseDonorInfo, $errorIdCheckResponse, [
                    'lpasCount' => 1,
                    'lpas' => [array_merge($lpaOneResponse, $errorIdCheckResponse)],
                ])
            ],
            // multiple errors
            [
                [$lpaOne, $lpaTwo],
                [$baseCheckLpaStatusResponse, $baseCheckLpaStatusResponse],
                [$errorIdCheckResponse, $errorIdCheckResponse],
                array_merge($baseResponse, [
                    'problem' => true,
                    'message' => 'These LPAs cannot be added, voucher details match with actors.'
                ])
            ],
            // one error, one happy LPA
            [
                [$lpaOne, $lpaTwo],
                [$baseCheckLpaStatusResponse, $baseCheckLpaStatusResponse],
                [$errorIdCheckResponse, $baseCheckLpaIdMatchResponse],
                array_merge($baseResponseDonorInfo, [
                    'lpasCount' => 1,
                    'lpas' => [ 1 => $lpaTwoResponse],
                ])
            ],
            // happy path with warnings
            [
                [$lpaOne, $lpaTwo],
                [$baseCheckLpaStatusResponse, $baseCheckLpaStatusResponse],
                [$warningIdCheckResponse, $baseCheckLpaIdMatchResponse],
                array_merge($baseResponseDonorInfo, [
                    "lpasCount" => 2,
                    'warning' => 'actor-match',
                    'message' => self::$cpMatchMessage,
                    'additionalRows' => self::getAdditionalRows('CP'),
                    "lpas" => [
                        array_merge($lpaOneResponse, [
                            'warning' => 'actor-match',
                            'message' => self::$cpMatchMessage,
                            'additionalRows' => self::getAdditionalRows('CP'),
                        ]),
                        $lpaTwoResponse,
                    ],
                ])
            ],
            // happy path
            [
                [$lpaOne, $lpaTwo],
                [$baseCheckLpaStatusResponse, $baseCheckLpaStatusResponse],
                [$baseCheckLpaIdMatchResponse, $baseCheckLpaIdMatchResponse],
                array_merge($baseResponseDonorInfo, [
                    "lpasCount" => 2,
                    "lpas" => [$lpaOneResponse, $lpaTwoResponse],
                ])
            ],
        ];
    }
}
