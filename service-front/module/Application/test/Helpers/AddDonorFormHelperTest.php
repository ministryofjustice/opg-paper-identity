<?php

declare(strict_types=1);

namespace ApplicationTest\Helpers;

use Application\Enums\LpaActorTypes;
use Application\Helpers\AddDonorFormHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AddDonorFormHelperTest extends TestCase
{
    private VoucherMatchLpaActorHelper&MockObject $matchHelperMock;
    private AddDonorFormHelper $addDonorFormHelper;

    public static array $baseLpa = [
        'opg.poas.sirius' => [
            'donor' => [
                'firstname' => 'First name',
                'surname' => 'LastName',
                'dob' => '10/02/1990',
                'addressLine1' => '123 Fakestreet',
                'town' => 'faketown',
                'postcode' => 'FA2 3KE'
            ]
        ]
    ];

    public static array $baseDetailsData = [
        'firstName' => 'Voucher',
        'lastName' => 'McVoucher',
        'dob' => '1990-11-15',
        'address' => [
            'line1' => '321 Pretend Road',
            'town' => 'NotReal',
            'postcode' => 'NR9 3PR'
        ],
        'lpas' => ['M-0000-0000-0000'],
    ];

    public function setUp(): void
    {
        $this->matchHelperMock = $this->createMock(VoucherMatchLpaActorHelper::class);

        parent::setUp();

        $this->addDonorFormHelper = new AddDonorFormHelper($this->matchHelperMock);
    }

    public function testGetDonorNameFromSiriusResponse(): void
    {
        $response = $this->addDonorFormHelper->getDonorNameFromSiriusResponse(self::$baseLpa);
        $this->assertEquals('First name LastName', $response);
    }

    public function testGetDonorDobFromSiriusResponse(): void
    {
        $response = $this->addDonorFormHelper->getDonorDobFromSiriusResponse(self::$baseLpa);
        $this->assertEquals('10 Feb 1990', $response);
    }

    /**
     * @dataProvider statusData
     */
    public function testCheckLpaStatus(array $lpaStoreData, array $expectedResult): void
    {
        $lpaData = self::$baseLpa;
        $lpaData['opg.poas.lpastore'] = $lpaStoreData;
        $response = $this->addDonorFormHelper->checkLpaStatus($lpaData);
        $this->assertEquals($expectedResult, $response);
    }

    public static function statusData(): array
    {
        return [
            [
                [],
                ['problem' => true, 'message' => 'No LPA Found.']
            ],
            [
                ['status' => 'complete'],
                [
                    'problem' => true,
                    'message' => 'This LPA cannot be added as an ID check has already been completed for this LPA.'
                ]
            ],
            [
                ['status' => 'draft'],
                [
                    'problem' => true,
                    'message' => 'This LPA cannot be added as it’s status is set to Draft. ' .
                        'LPAs need to be in the In Progress status to be added to this ID check.'
                    ]
                ],
            [
                ['status' => 'In progress'],
                ['problem' => false, 'message' => '']
            ]
        ];
    }

    /**
     * @dataProvider idMatchData
     */
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
            // ->with($this->uuid)
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

        $lpa = self::$baseLpa;
        if (! is_null($lpastore)) {
            $lpa['opg.poas.lpastore'] = $lpastore;
        }

        $response = $this->addDonorFormHelper->checkLpaIdMatch($lpa, self::$baseDetailsData);
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

        $nameDobMatchDonor = [
            'firstName' => 'Matchfirst',
            'lastName' => 'MatchLast',
            'dob' => '1990-10-11',
            'type' => LpaActorTypes::DONOR->value,
        ];

        $nameDobMatchDonorResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot have the same name and date of birth as the donor.',
            'warning' => 'donor-match'
        ]);

        $nameDobMatchAttorney = [
            'firstName' => 'Matchfirst',
            'lastName' => 'MatchLast',
            'dob' => '1990-10-11',
            'type' => LpaActorTypes::ATTORNEY->value,
        ];

        $nameDobMatchAttorneyResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot have the same name and date of birth as an attorney.',
            'warning' => 'actor-match',
            'additionalRows' => [
                [
                    'type' => 'Attorney name',
                    'value' => 'Matchfirst MatchLast'
                ],
                [
                    'type' => 'Attorney date of birth',
                    'value' => '11 Oct 1990'
                ]
            ]
        ]);

        $addressMatchDonorResponse = array_merge($emptyResponse, [
            'error' => true,
            'message' => 'The person vouching cannot live at the same address as the donor.',
            'warning' => 'address-match'
        ]);

        $nameMatchCpLpaStore = [
            'certificateProvider' => [
                'firstNames' => 'CPName',
                'lastName' => 'CPSurname'
            ]
            ];

        $nameMatchCpResponse = array_merge($emptyResponse, [
            'message' => 'There is a certificate provider called CPName CPSurname named on this LPA. ' .
                'A certificate provider cannot vouch for the identity of a donor. ' .
                'Confirm that these are two different people with the same name.',
            'warning' => 'actor-match',
            'additionalRows' => [
                [
                    'type' => 'Certificate provider name',
                    'value' => 'CPName CPSurname'
                ]
            ]
        ]);

        return [
            // no matches
            [false, false, false, null, $emptyResponse],
            // match on name and dob with DONOR
            [$nameDobMatchDonor, null, null, null, $nameDobMatchDonorResponse],
            // match on name and dob with ATTORNEY
            [$nameDobMatchAttorney, null, null, null, $nameDobMatchAttorneyResponse],
            // match on address with DONOR
            [false, true, null, null, $addressMatchDonorResponse],
            // match on name with CP
            [false, false, true, $nameMatchCpLpaStore, $nameMatchCpResponse],
        ];
    }

    /**
     * @dataProvider processLpasData
     */
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

        $response = $helper->processLpas($lpasData, self::$baseDetailsData);
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
            'donorName' => 'First name LastName',
            'donorDob' => '10 Feb 1990',
            'donorAddress' => [
                'line1' => '123 Fakestreet',
                'line2' => '',
                'line3' => '',
                'town' => 'faketown',
                'postcode' => 'FA2 3KE',
                'country' => ''
            ],
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

        $noLpaResopnse = array_merge($baseResponse, [
            'problem' => true,
            'message' => 'No LPA Found.'
        ]);

        $lpaOnIdCheck = self::$baseLpa;
        $lpaOnIdCheck['opg.poas.sirius']['uId'] = 'M-0000-0000-0000';

        $lpaOnIdCheckResponse = array_merge($baseResponse, [
            'problem' => true,
            'message' => 'This LPA has already been added to this identity check.'
        ]);

        $problemStatusResponse = [
            'problem' => true,
            'message' => 'This LPA cannot be added as an ID check has already been completed for this LPA.',
        ];

        $errorIdCheckResponse = [
            'problem' => false,
            'error' => true,
            'warning' => 'actor-match',
            'message' => 'The person vouching cannot have the same name and date of birth as an attorney.',
            'additionalRows' => [
                [
                    'type' => 'Attorney name',
                    'value' => 'some name'
                ],
                [
                    'type' => 'Attorney date of birth',
                    'value' => '11 Jan 1980'
                ]
            ],
        ];

        $cp_message = 'There is a certificate provider called some name named on this LPA. ' .
            'A certificate provider cannot vouch for the identity of a donor. ' .
            'Confirm that these are two different people with the same name.';

        $warningIdCheckResponse = [
            'problem' => false,
            'error' => false,
            'warning' => 'actor-match',
            'message' => $cp_message,
            'additionalRows' => [
                [
                    'type' => 'Certificate provider name',
                    'value' => 'some name'
                ],
            ],
        ];

        $singleProblemResponse = array_merge($baseResponse, [
            'problem' => true,
            'message' => 'This LPA cannot be added as an ID check has already been completed for this LPA.',
        ]);

        $multipleProblemResponse = array_merge($baseResponse, [
            'problem' => true,
            'message' => 'These LPAs cannot be added.',
        ]);

        $lpaOne = self::$baseLpa;
        $lpaOne['opg.poas.sirius']['uId'] = 'M-1111-1111-1111';
        $lpaOne['opg.poas.sirius']['caseSubtype'] = 'personal-welfare';

        $lpaTwo = self::$baseLpa;
        $lpaTwo['opg.poas.sirius']['uId'] = 'M-2222-2222-2222';
        $lpaTwo['opg.poas.sirius']['caseSubtype'] = 'property-and-affairs';

        return [
            // no LPAs
            [[], null, null, $noLpaResopnse],
            // lpa already on Id Check
            [[$lpaOnIdCheck], null, null, $lpaOnIdCheckResponse],
            // single problem LPA
            [[$lpaOne], [$problemStatusResponse], null, $singleProblemResponse],
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
                    'lpas' => [ 1 => array_merge($baseCheckLpaIdMatchResponse, [
                            'uId' => 'M-2222-2222-2222',
                            'type' => 'PA'
                        ])
                    ]
                ])
            ],
            // one error
            [
                [$lpaOne],
                [$baseCheckLpaStatusResponse],
                [$errorIdCheckResponse],
                array_merge($baseResponseDonorInfo, $errorIdCheckResponse, [
                    'lpasCount' => 1,
                    'lpas' => [
                        array_merge($errorIdCheckResponse, [
                            'uId' => 'M-1111-1111-1111',
                            'type' => 'PW',
                            'error' => true,
                        ])
                    ],
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
                    'lpas' => [ 1 =>
                        array_merge($baseCheckLpaIdMatchResponse, [
                            'uId' => 'M-2222-2222-2222',
                            'type' => 'PA',
                        ])
                    ],
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
                    'message' => $cp_message,
                    'additionalRows' => [
                        [
                            'type' => 'Certificate provider name',
                            'value' => 'some name',
                        ]
                    ],
                    "lpas" => [
                        array_merge($baseCheckLpaIdMatchResponse, [
                            'uId' => 'M-1111-1111-1111',
                            'type' => 'PW',
                            'warning' => 'actor-match',
                            'message' => $cp_message,
                            'additionalRows' => [
                                [
                                    'type' => 'Certificate provider name',
                                    'value' => 'some name',
                                ]
                            ],
                        ]),
                        array_merge($baseCheckLpaIdMatchResponse, [
                            'uId' => 'M-2222-2222-2222',
                            'type' => 'PA'
                        ]),
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
                    "lpas" => [
                        array_merge($baseCheckLpaIdMatchResponse, [
                            'uId' => 'M-1111-1111-1111',
                            'type' => 'PW'
                        ]),
                        array_merge($baseCheckLpaIdMatchResponse, [
                            'uId' => 'M-2222-2222-2222',
                            'type' => 'PA'
                        ]),
                    ],
                ])
            ],
        ];
    }
}
